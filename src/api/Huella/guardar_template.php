<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';

$db = (new Database())->getConnection();

$id_persona = $_POST['id_persona'] ?? $_POST['id_estudiante'] ?? null;
$tipo_persona = $_POST['tipo_persona'] ?? 'estudiante';
$template = $_POST['template'] ?? null;

function avisarGatewaySync(): array
{
    $url = GATEWAY_SYNC_URL;
    $headers = [
        "X-GATEWAY-KEY: " . GATEWAY_API_KEY
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        $respuesta = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            "avisado" => $respuesta !== false && $httpCode >= 200 && $httpCode < 300,
            "http_code" => $httpCode,
            "error" => $respuesta === false ? $error : null,
            "url" => $url
        ];
    }

    $context = stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => implode("\r\n", $headers),
            "timeout" => 3,
            "ignore_errors" => true
        ]
    ]);

    $respuesta = @file_get_contents($url, false, $context);
    $httpCode = 0;

    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $matches)) {
        $httpCode = (int) $matches[1];
    }

    return [
        "avisado" => $respuesta !== false && $httpCode >= 200 && $httpCode < 300,
        "http_code" => $httpCode,
        "error" => $respuesta === false ? "No se pudo invocar el gateway" : null,
        "url" => $url
    ];
}

if (!$id_persona || !$template) {
    echo json_encode(["status" => "error", "message" => "Faltan datos"]);
    exit;
}

try {
    if ($tipo_persona === "profesor") {
        $tabla = "profesores";
        $idCampo = "id_profesor";
        $user_id_global = "PROF_" . $id_persona;
        $id_aula = null;
    } else {
        $tabla = "estudiantes";
        $idCampo = "id_estudiante";
        $user_id_global = "EST_" . $id_persona;

        $stmtEstudiante = $db->prepare("
            SELECT g.id_aula
            FROM estudiantes e
            LEFT JOIN grados g
                ON g.id_grado = e.id_grado
               AND g.activo = 1
            WHERE e.id_estudiante = :id_estudiante
              AND e.activo = 1
            LIMIT 1
        ");
        $stmtEstudiante->execute([":id_estudiante" => $id_persona]);
        $estudiante = $stmtEstudiante->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Estudiante no encontrado"]);
            exit;
        }

        $id_aula = $estudiante["id_aula"] ?? null;

        if (!$id_aula) {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "El estudiante no tiene aula asignada. No se puede sincronizar la huella."
            ]);
            exit;
        }
    }

    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO huellas_templates
        (user_id_global, {$idCampo}, fingerprint_data, formato, pendiente_sync, activo)
        VALUES
        (:user_id_global, :id_persona, :fingerprint_data, 'HEX', 1, 1)
    ");

    $stmt->execute([
        ":user_id_global" => $user_id_global,
        ":id_persona" => $id_persona,
        ":fingerprint_data" => $template
    ]);

    $id_huella = $db->lastInsertId();

    $stmt3 = $db->prepare("
        INSERT INTO sync_biometrica
        (id_huella, id_aula, estado, intentos)
        VALUES
        (:id_huella, :id_aula, 'PENDIENTE', 0)
    ");

    $stmt3->execute([
        ":id_huella" => $id_huella,
        ":id_aula" => $id_aula
    ]);
    
    $id_sync = $db->lastInsertId();

    $db->commit();

    $gatewaySync = avisarGatewaySync();

    echo json_encode([
        "status" => "success",
        "message" => "Huella guardada correctamente y pendiente para Gateway",
        "id_huella" => $id_huella,
        "gateway_avisado" => $gatewaySync["avisado"],
        "gateway_http_code" => $gatewaySync["http_code"],
        "gateway_error" => $gatewaySync["error"],
        "gateway_url" => $gatewaySync["url"],
        "id_sync" => $id_sync
    ]);

} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();

    echo json_encode([
        "status" => "error",
        "message" => "Error al guardar template",
        "debug" => $e->getMessage()
    ]);
}
