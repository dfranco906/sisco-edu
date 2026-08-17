<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/biometria.php';
require_once __DIR__ . '/../../classes/SyncBiometrica.php';

$db = (new Database())->getConnection();
$sync = new SyncBiometrica($db);

$id_huella = $_POST['id_huella'] ?? null;
$id_aula_recibido = isset($_POST['id_aula']) ? (int) $_POST['id_aula'] : null;

if (!$id_huella) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Falta id_huella"]);
    exit;
}

$stmtHuella = $db->prepare("
    SELECT
        h.user_id_global,
        h.fingerprint_data,
        h.formato,
        e.id_estudiante,
        p.id_profesor,
        g.id_aula AS id_aula_estudiante
    FROM huellas_templates h
    LEFT JOIN estudiantes e ON e.activo = 1
        AND (e.id_estudiante = h.id_estudiante OR (h.id_estudiante IS NULL AND e.user_id_global = h.user_id_global))
    LEFT JOIN grados g ON e.id_grado = g.id_grado AND g.activo = 1
    LEFT JOIN profesores p ON p.activo = 1
        AND (p.id_profesor = h.id_profesor OR (h.id_profesor IS NULL AND p.user_id_global = h.user_id_global))
    WHERE h.id_huella = :id_huella
      AND h.activo = 1
    LIMIT 1
");
$stmtHuella->execute([":id_huella" => $id_huella]);
$huella = $stmtHuella->fetch(PDO::FETCH_ASSOC);

if (!$huella) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Huella no encontrada"]);
    exit;
}

$template = trim((string)$huella["fingerprint_data"]);
if (strtoupper((string)$huella["formato"]) !== 'HEX' || !es_template_huella_hex_valido($template)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Template incompatible: se requieren 1536 bytes HEX"]);
    exit;
}

if ($huella["id_estudiante"] !== null) {
    if ($id_aula_recibido !== null) {
        $stmtAula = $db->prepare("
            SELECT id_aula
            FROM aulas
            WHERE id_aula = :id_aula
              AND activo = 1
            LIMIT 1
        ");
        $stmtAula->execute([":id_aula" => $id_aula_recibido]);
        $id_aula = $stmtAula->fetchColumn() ?: null;
    } else {
        $id_aula = $huella["id_aula_estudiante"] ?? null;
    }

    if (!$id_aula) {
        http_response_code(422);
        echo json_encode([
            "status" => "error",
            "message" => "El estudiante no tiene aula asignada. No se puede sincronizar la huella al aula."
        ]);
        exit;
    }
} else {
    $id_aula = $id_aula_recibido;
}

$sync->id_huella = $id_huella;
$sync->id_aula = $id_aula;
$sync->estado = $_POST['estado'] ?? 'PENDIENTE';
$sync->intentos = $_POST['intentos'] ?? 0;

$resultado = $sync->crear();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Sync creado" : "Error al crear sync",
    "id_aula" => $resultado ? $id_aula : null
]);
