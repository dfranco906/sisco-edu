<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/ClaseDiaria.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Control de acceso institucional: Profesores, Coordinadores y Administradores pueden marcar manual inicial.
// Sola y exclusivamente Administradores pueden modificar o revertir asistencias previas.
$rol = $_SESSION['rol'] ?? '';
$rolesAdmin = ['SuperAdmin', 'Administrador', 'Administracion', 'admin'];
$rolesOperativos = ['Profesor', 'Coordinador'];

$esAdmin = in_array($rol, $rolesAdmin, true) || in_array(strtolower($rol), ['superadmin', 'administrador', 'administracion', 'admin'], true);
$esOperativo = in_array($rol, $rolesOperativos, true) || in_array(strtolower($rol), ['profesor', 'coordinador'], true);

if (!$esAdmin && !$esOperativo) {
    http_response_code(403);
    echo json_encode([
        "status" => "error",
        "message" => "Acceso denegado: solo profesores, coordinadores y administradores tienen permisos para registrar asistencia manual."
    ]);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

$tipo_persona = strtolower(trim($data['tipo_persona'] ?? 'estudiante'));
if (!in_array($tipo_persona, ['estudiante', 'profesor'], true)) {
    $tipo_persona = 'estudiante';
}

$id_estudiante = filter_var($data['id_estudiante'] ?? ($tipo_persona === 'estudiante' ? ($data['id_persona'] ?? null) : null), FILTER_VALIDATE_INT);
$id_profesor = filter_var($data['id_profesor'] ?? ($tipo_persona === 'profesor' ? ($data['id_persona'] ?? null) : null), FILTER_VALIDATE_INT);
$fecha = trim($data['fecha'] ?? date('Y-m-d'));
$hora = trim($data['hora'] ?? date('H:i:s'));
$estado = strtoupper(trim($data['estado'] ?? 'PRESENTE'));
$id_horario = filter_var($data['id_horario'] ?? null, FILTER_VALIDATE_INT) ?: null;

$estadosValidos = ['PRESENTE', 'TARDANZA', 'AUSENTE', 'JUSTIFICADO'];
if (!in_array($estado, $estadosValidos, true)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Estado de asistencia inválido"]);
    exit;
}

if ($tipo_persona === 'estudiante' && !$id_estudiante) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Se requiere un id_estudiante válido"]);
    exit;
}

if ($tipo_persona === 'profesor' && !$id_profesor) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Se requiere un id_profesor válido"]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $claseModel = new ClaseDiaria($db);

    // 1. Consultar si ya existe marcación activa de asistencia para esta persona en la fecha dada
    $sqlExistente = ($tipo_persona === 'estudiante')
        ? "SELECT id_asistencia_estudiante as id, estado, tipo_registro, hora_marcacion, hora FROM asistencias_estudiantes WHERE id_estudiante = :id AND fecha = :fecha AND activo = 1 LIMIT 1"
        : "SELECT id_asistencia_profesor as id, estado, tipo_registro, hora_marcacion, hora FROM asistencias_profesores WHERE id_profesor = :id AND fecha = :fecha AND activo = 1 LIMIT 1";

    $stmtExist = $db->prepare($sqlExistente);
    $stmtExist->execute([
        ':id' => ($tipo_persona === 'estudiante' ? $id_estudiante : $id_profesor),
        ':fecha' => $fecha
    ]);
    $existente = $stmtExist->fetch(PDO::FETCH_ASSOC);

    $tieneAsistenciaPrevia = ($existente && $existente['estado'] !== 'AUSENTE');

    // 2. Si ya cuenta con asistencia activa (PRESENTE, TARDANZA, JUSTIFICADO):
    //    Es una acción de MODIFICACIÓN o REVERSIÓN
    if ($tieneAsistenciaPrevia) {
        // Regla A: Si fue por huella dactilar (BIOMETRICO), es INMUTABLE (nadie puede modificarla)
        if (($existente['tipo_registro'] ?? '') === 'BIOMETRICO') {
            http_response_code(422);
            echo json_encode([
                "status" => "error",
                "message" => "La asistencia fue registrada mediante huella dactilar (biométrica) en el aula y es inmutable. No se permite su modificación ni reversión."
            ]);
            exit;
        }

        // Regla B: Si fue manual, sola y exclusivamente los Administradores pueden modificarla o revertirla
        if (!$esAdmin) {
            http_response_code(403);
            echo json_encode([
                "status" => "error",
                "message" => "Acceso denegado: sola y exclusivamente los administradores pueden modificar o revertir asistencias ya registradas."
            ]);
            exit;
        }
    }

    // 3. Caso de Marcación / Reversión a AUSENTE
    if ($estado === 'AUSENTE') {
        if ($existente) {
            if ($existente['estado'] === 'AUSENTE') {
                http_response_code(200);
                echo json_encode([
                    "status" => "success",
                    "message" => "La persona ya se encuentra registrada como AUSENTE",
                    "data" => ["estado" => "AUSENTE"]
                ]);
                exit;
            }

            // Si llegó aquí con un registro previo activo, ya se validó que es MANUAL y que el usuario es Administrador
            $sqlAus = ($tipo_persona === 'estudiante')
                ? "UPDATE asistencias_estudiantes SET estado = 'AUSENTE', tipo_registro = 'MANUAL', nodo_origen = 'PANEL_CLASE_EN_VIVO', fecha_registro = CURRENT_TIMESTAMP WHERE id_asistencia_estudiante = :id"
                : "UPDATE asistencias_profesores SET estado = 'AUSENTE', tipo_registro = 'MANUAL', nodo_origen = 'PANEL_CLASE_EN_VIVO', fecha_registro = CURRENT_TIMESTAMP WHERE id_asistencia_profesor = :id";
            
            $stmtAus = $db->prepare($sqlAus);
            $stmtAus->execute([':id' => $existente['id']]);

            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Asistencia revertida a AUSENTE correctamente por el Administrador",
                "data" => [
                    "tipo_persona" => $tipo_persona,
                    "id_persona" => $tipo_persona === 'estudiante' ? $id_estudiante : $id_profesor,
                    "estado" => "AUSENTE",
                    "fecha" => $fecha
                ]
            ]);
            exit;
        }

        // Si NO existía registro previo, es una marcación inicial de AUSENTE durante la llamada de lista
        if ($tipo_persona === 'estudiante') {
            $resultado = $claseModel->registrarAsistenciaEstudiante(
                $id_estudiante,
                $fecha,
                $hora,
                'AUSENTE',
                'MANUAL',
                'PANEL_CLASE_EN_VIVO',
                $id_horario,
                true
            );
        } else {
            $resultado = $claseModel->registrarAsistenciaProfesor(
                $id_profesor,
                $fecha,
                $hora,
                'AUSENTE',
                'MANUAL',
                'PANEL_CLASE_EN_VIVO',
                $id_horario,
                true
            );
        }

        $httpCode = $resultado['exito'] ? 200 : 422;
        http_response_code($httpCode);
        echo json_encode([
            "status" => $resultado['exito'] ? "success" : "error",
            "message" => $resultado['mensaje'] ?? "Marcado como AUSENTE correctamente",
            "data" => $resultado
        ]);
        exit;
    }

    // 4. Registro o rectificación manual de asistencia (PRESENTE, TARDANZA, JUSTIFICADO)
    if ($tipo_persona === 'estudiante') {
        $resultado = $claseModel->registrarAsistenciaEstudiante(
            $id_estudiante,
            $fecha,
            $hora,
            $estado,
            'MANUAL',
            'PANEL_CLASE_EN_VIVO',
            $id_horario,
            true // forzar_actualizacion = true para permitir cambio manual de estado sin crear filas duplicadas
        );
    } else {
        $resultado = $claseModel->registrarAsistenciaProfesor(
            $id_profesor,
            $fecha,
            $hora,
            $estado,
            'MANUAL',
            'PANEL_CLASE_EN_VIVO',
            $id_horario,
            true
        );
    }

    $httpCode = $resultado['exito'] ? 200 : 422;
    http_response_code($httpCode);
    echo json_encode([
        "status" => $resultado['exito'] ? "success" : "error",
        "duplicado" => !empty($resultado['duplicado']),
        "actualizado" => !empty($resultado['actualizado']),
        "message" => $resultado['mensaje'] ?? "Asistencia manual procesada",
        "data" => $resultado
    ]);
} catch (Throwable $e) {
    error_log("Error en Clases/marcar_asistencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Error interno al actualizar asistencia"]);
}
