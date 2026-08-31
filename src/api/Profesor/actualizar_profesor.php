<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Profesor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "status" => "error", "message" => "Método no permitido."]);
    exit;
}

$idProfesor = filter_var($_POST['id_profesor'] ?? null, FILTER_VALIDATE_INT);
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$apellido = trim((string) ($_POST['apellido'] ?? ''));
$cedula = trim((string) ($_POST['cedula_identidad'] ?? ''));
$idUsuario = filter_var($_POST['id_usuario'] ?? 0, FILTER_VALIDATE_INT) ?: null;

if (!$idProfesor || $nombre === '' || $apellido === '' || $cedula === '') {
    http_response_code(422);
    echo json_encode(["success" => false, "status" => "error", "message" => "Profesor, nombre, apellido y cédula son obligatorios."]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmtActual = $db->prepare("SELECT user_id_global FROM profesores WHERE id_profesor = :id LIMIT 1");
    $stmtActual->execute([":id" => $idProfesor]);
    $userIdActual = $stmtActual->fetchColumn();

    if ($userIdActual === false) {
        http_response_code(404);
        echo json_encode(["success" => false, "status" => "error", "message" => "El profesor no existe."]);
        exit;
    }

    if ($idUsuario) {
        $cuenta = $db->prepare("SELECT COUNT(*) FROM usuarios u LEFT JOIN profesores p ON p.id_usuario=u.id_usuario AND p.id_profesor<>:profesor WHERE u.id_usuario=:id AND u.rol='Profesor' AND u.activo=1 AND p.id_profesor IS NULL");
        $cuenta->execute([':id'=>$idUsuario,':profesor'=>$idProfesor]);
        if (!(int)$cuenta->fetchColumn()) { http_response_code(409); echo json_encode(['success'=>false,'status'=>'error','message'=>'La cuenta no es de profesor o ya esta vinculada.']); exit; }
    }

    $profesor = new Profesor($db);
    $profesor->id_usuario = $idUsuario;
    $profesor->id_profesor = $idProfesor;
    $profesor->nombre = $nombre;
    $profesor->apellido = $apellido;
    $profesor->cedula_identidad = $cedula;
    $profesor->user_id_global = trim((string) ($_POST['user_id_global'] ?? ''))
        ?: ($userIdActual ?: ('PROF_' . uniqid() . '_' . random_int(100, 999)));

    $resultado = $profesor->actualizar();
    echo json_encode([
        "success" => (bool) $resultado,
        "status" => $resultado ? "success" : "error",
        "message" => $resultado ? "Profesor actualizado correctamente." : "No se pudo actualizar el profesor.",
        "data" => $resultado ? ["id_profesor" => $idProfesor] : null
    ]);
} catch (PDOException $e) {
    error_log('actualizar_profesor: ' . $e->getMessage());
    $duplicado = $e->getCode() === '23000';
    http_response_code($duplicado ? 409 : 500);
    echo json_encode([
        "success" => false,
        "status" => "error",
        "message" => $duplicado ? "La cédula ya está registrada para otro profesor." : "No se pudo actualizar el profesor."
    ]);
} catch (Throwable $e) {
    error_log('actualizar_profesor: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "No se pudo actualizar el profesor."]);
}
?>
