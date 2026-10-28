<?php
declare(strict_types=1);

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/db.php';

$idProfesor = isset($_GET['id_profesor']) && is_numeric($_GET['id_profesor']) ? (int)$_GET['id_profesor'] : null;
$userIdGlobal = trim($_GET['user_id_global'] ?? '');
$incluirTodas = isset($_GET['todas']) && (string)$_GET['todas'] === '1';

if (!$idProfesor && empty($userIdGlobal)) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Se requiere el ID o user_id_global del profesor"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = (new Database())->getConnection();

    // 1. Obtener profesor
    if ($idProfesor) {
        $stmtP = $db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad, user_id_global, huella_id FROM profesores WHERE id_profesor = :id AND activo = 1 LIMIT 1");
        $stmtP->execute([':id' => $idProfesor]);
    } else {
        $stmtP = $db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad, user_id_global, huella_id FROM profesores WHERE user_id_global = :uid AND activo = 1 LIMIT 1");
        $stmtP->execute([':uid' => $userIdGlobal]);
    }
    $profesor = $stmtP->fetch(PDO::FETCH_ASSOC);

    if (!$profesor) {
        http_response_code(404);
        echo json_encode([
            "status" => "error",
            "message" => "Profesor no encontrado o inactivo"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idProfesor = (int)$profesor['id_profesor'];
    $userIdGlobal = $profesor['user_id_global'];

    // 2. Obtener huella activa del profesor
    $stmtH = $db->prepare("
        SELECT id_huella, bytes, crc32_hash, fecha_creacion
        FROM huellas_templates
        WHERE (id_profesor = :id OR (user_id_global IS NOT NULL AND user_id_global = :uid))
          AND activo = 1
        ORDER BY id_huella DESC
        LIMIT 1
    ");
    $stmtH->execute([':id' => $idProfesor, ':uid' => $userIdGlobal]);
    $huella = $stmtH->fetch(PDO::FETCH_ASSOC);

    $idHuella = $huella ? (int)$huella['id_huella'] : null;

    // 3. Obtener aulas asignadas al docente por sus horarios y asignaciones
    $stmtAulasAsignadas = $db->prepare("
        SELECT DISTINCT 
            a.id_aula,
            a.nombre AS aula_nombre,
            a.codigo AS aula_codigo,
            a.codigo_sala,
            a.ubicacion,
            n.lora_id,
            STRING_AGG(DISTINCT g.nombre, ', ') AS grados,
            STRING_AGG(DISTINCT m.nombre, ', ') AS materias
        FROM asignacion_docente ad
        INNER JOIN materias m ON m.id_materia = ad.id_materia
        LEFT JOIN grados g ON g.id_grado = ad.id_grado
        LEFT JOIN horarios h ON h.id_asignacion = ad.id_asignacion AND h.activo = 1
        INNER JOIN aulas a ON a.id_aula = COALESCE(h.id_aula, g.id_aula) AND a.activo = 1
        LEFT JOIN nodos_esp32 n ON n.id_aula = a.id_aula AND n.activo = 1
        WHERE ad.id_profesor = :id_profesor AND ad.activo = 1
        GROUP BY a.id_aula, a.nombre, a.codigo, a.codigo_sala, a.ubicacion, n.lora_id
        ORDER BY a.nombre ASC
    ");
    $stmtAulasAsignadas->execute([':id_profesor' => $idProfesor]);
    $aulasAsignadas = $stmtAulasAsignadas->fetchAll(PDO::FETCH_ASSOC);

    $mapaAulas = [];
    foreach ($aulasAsignadas as $a) {
        $idA = (int)$a['id_aula'];
        $mapaAulas[$idA] = [
            "id_aula" => $idA,
            "nombre" => $a['aula_nombre'],
            "codigo" => $a['aula_codigo'],
            "codigo_sala" => $a['codigo_sala'],
            "ubicacion" => $a['ubicacion'],
            "lora_id" => $a['lora_id'],
            "grados" => $a['grados'] ?: 'Sin grado específico',
            "materias" => $a['materias'] ?: 'Sin materias específicas',
            "asignada" => true
        ];
    }

    // Si se solicitó incluir todas las aulas del colegio
    if ($incluirTodas) {
        $stmtTodasAulas = $db->query("
            SELECT a.id_aula, a.nombre, a.codigo, a.codigo_sala, a.ubicacion, n.lora_id
            FROM aulas a
            LEFT JOIN nodos_esp32 n ON n.id_aula = a.id_aula AND n.activo = 1
            WHERE a.activo = 1
            ORDER BY a.nombre ASC
        ");
        while ($t = $stmtTodasAulas->fetch(PDO::FETCH_ASSOC)) {
            $idA = (int)$t['id_aula'];
            if (!isset($mapaAulas[$idA])) {
                $mapaAulas[$idA] = [
                    "id_aula" => $idA,
                    "nombre" => $t['nombre'],
                    "codigo" => $t['codigo'],
                    "codigo_sala" => $t['codigo_sala'],
                    "ubicacion" => $t['ubicacion'],
                    "lora_id" => $t['lora_id'],
                    "grados" => "Otras aulas",
                    "materias" => "-",
                    "asignada" => false
                ];
            }
        }
    }

    // 4. Cruzar con el estado real de sincronización (aula_huellas_sync y sync_biometrica)
    $listaAulasFinal = [];
    $totalSincronizadas = 0;
    $totalPendientes = 0;
    $totalFaltantes = 0;

    foreach ($mapaAulas as $idA => $itemAula) {
        $estadoSync = "NO_ENVIADO";
        $slotLocal = null;
        $fechaSync = null;
        $mensajeSync = "Huella no enviada a este sensor de aula";

        if ($idHuella) {
            // Verificar registro permanente confirmado en hardware
            $stmtConf = $db->prepare("
                SELECT id_aula_sync, slot_local, estado, fecha_registro
                FROM aula_huellas_sync
                WHERE id_huella = :id_huella AND id_aula = :id_aula
                ORDER BY id_aula_sync DESC
                LIMIT 1
            ");
            $stmtConf->execute([':id_huella' => $idHuella, ':id_aula' => $idA]);
            $conf = $stmtConf->fetch(PDO::FETCH_ASSOC);

            if ($conf && strtoupper((string)$conf['estado']) === 'CONFIRMADO') {
                $estadoSync = "SINCRONIZADO";
                $slotLocal = (int)$conf['slot_local'];
                $fechaSync = $conf['fecha_registro'];
                $mensajeSync = "Huella guardada en el sensor DY50 (Slot #{$slotLocal})";
                $totalSincronizadas++;
            } else {
                // Verificar si hay una sincronización en cola o fallida en sync_biometrica
                $stmtCola = $db->prepare("
                    SELECT id_sync, estado, intentos, mensaje, fecha_actualizacion, fecha_creacion
                    FROM sync_biometrica
                    WHERE id_huella = :id_huella AND id_aula = :id_aula
                    ORDER BY id_sync DESC
                    LIMIT 1
                ");
                $stmtCola->execute([':id_huella' => $idHuella, ':id_aula' => $idA]);
                $cola = $stmtCola->fetch(PDO::FETCH_ASSOC);

                if ($cola) {
                    $st = strtoupper((string)$cola['estado']);
                    if ($st === 'PENDIENTE' || $st === 'ENVIANDO') {
                        $estadoSync = "PENDIENTE";
                        $fechaSync = $cola['fecha_creacion'];
                        $mensajeSync = "En cola de transmisión LoRa (Intento {$cola['intentos']})";
                        $totalPendientes++;
                    } elseif ($st === 'ERROR') {
                        $estadoSync = "ERROR";
                        $fechaSync = $cola['fecha_actualizacion'] ?: $cola['fecha_creacion'];
                        $mensajeSync = "Fallo de sincronización: " . ($cola['mensaje'] ?: 'Sin respuesta del sensor');
                        $totalFaltantes++;
                    } else {
                        $estadoSync = "SINCRONIZADO";
                        $fechaSync = $cola['fecha_actualizacion'];
                        $mensajeSync = "Sincronizado correctamente";
                        $totalSincronizadas++;
                    }
                } else {
                    $totalFaltantes++;
                }
            }
        } else {
            $totalFaltantes++;
        }

        $itemAula['estado_sync'] = $estadoSync;
        $itemAula['slot_local'] = $slotLocal;
        $itemAula['fecha_sync'] = $fechaSync;
        $itemAula['mensaje_sync'] = $mensajeSync;

        $listaAulasFinal[] = $itemAula;
    }

    http_response_code(200);
    echo json_encode([
        "status" => "success",
        "message" => "Estado de sincronización de huella por aula obtenido",
        "data" => [
            "profesor" => [
                "id_profesor" => $idProfesor,
                "nombre_completo" => trim("{$profesor['nombre']} {$profesor['apellido']}"),
                "cedula" => $profesor['cedula_identidad'],
                "user_id_global" => $userIdGlobal,
                "tiene_huella" => ($idHuella !== null),
                "id_huella" => $idHuella
            ],
            "resumen" => [
                "total_aulas" => count($listaAulasFinal),
                "sincronizadas" => $totalSincronizadas,
                "pendientes" => $totalPendientes,
                "faltantes" => $totalFaltantes
            ],
            "aulas" => $listaAulasFinal
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log("Error en Huella/aulas_profesor_sync: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Error interno al consultar estado de sincronización de aulas",
        "debug" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
