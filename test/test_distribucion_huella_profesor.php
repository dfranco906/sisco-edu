<?php
require_once __DIR__ . '/../src/config/db.php';
require_once __DIR__ . '/../src/classes/DistribucionHuellaProfesor.php';

function verificarDistribucion(bool $condicion, string $mensaje): void
{
    if (!$condicion) throw new RuntimeException($mensaje);
    echo "OK: {$mensaje}\n";
}

$db = (new Database())->getConnection();
$token = 'TEST_HUELLA_' . bin2hex(random_bytes(4));

try {
    $db->beginTransaction();
    $stmt = $db->prepare('INSERT INTO aulas (nombre,codigo,ubicacion,activo) VALUES (:n,:c,:u,1)');
    $stmt->execute([':n'=>$token.' Aula', ':c'=>$token, ':u'=>'Prueba']);
    $idAula = (int) $db->lastInsertId();
    $stmt = $db->prepare('INSERT INTO grados (nombre,id_aula,room_id,activo) VALUES (:n,:a,:r,1)');
    $stmt->execute([':n'=>$token.' Grado', ':a'=>$idAula, ':r'=>$token]);
    $idGrado = (int) $db->lastInsertId();
    $stmt = $db->prepare('INSERT INTO profesores (nombre,apellido,cedula_identidad,user_id_global,activo) VALUES (:n,:a,:c,:u,1)');
    $stmt->execute([':n'=>'Profesor', ':a'=>$token, ':c'=>$token, ':u'=>$token]);
    $idProfesor = (int) $db->lastInsertId();
    $stmt = $db->prepare('INSERT INTO materias (nombre,activo) VALUES (:n,1)');
    $stmt->execute([':n'=>$token.' Materia']);
    $idMateria = (int) $db->lastInsertId();
    $stmt = $db->prepare('INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (:p,:m,:g,1,2026,1)');
    $stmt->execute([':p'=>$idProfesor, ':m'=>$idMateria, ':g'=>$idGrado]);
    $idAsignacion = (int) $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO horarios (id_asignacion,id_grado,id_aula,dia_semana,hora_inicio,hora_fin,activo) VALUES (:a,:g,:u,'Sábado','20:00','20:40',1)");
    $stmt->execute([':a'=>$idAsignacion, ':g'=>$idGrado, ':u'=>$idAula]);
    $stmt = $db->prepare("INSERT INTO huellas_templates (user_id_global,id_profesor,fingerprint_data,formato,pendiente_sync,activo) VALUES (:u,:p,:t,'HEX',1,1)");
    $stmt->execute([':u'=>$token, ':p'=>$idProfesor, ':t'=>str_repeat('ab',1536)]);
    $idHuella = (int) $db->lastInsertId();

    $servicio = new DistribucionHuellaProfesor($db);
    $aulas = $servicio->aulasAsignadas($idProfesor, $idHuella);
    verificarDistribucion(count($aulas) === 1 && (int)$aulas[0]['id_aula'] === $idAula, 'lista únicamente aulas del horario del profesor');
    $primera = $servicio->encolar($idHuella, $idProfesor, [$idAula]);
    verificarDistribucion(count($primera['creadas']) === 1, 'crea una sincronización para el aula elegida');
    $segunda = $servicio->encolar($idHuella, $idProfesor, [$idAula]);
    verificarDistribucion(count($segunda['creadas']) === 0 && count($segunda['omitidas']) === 1, 'la sincronización es idempotente');
    $rechazada = false;
    try { $servicio->encolar($idHuella, $idProfesor, [999999]); }
    catch (InvalidArgumentException $e) { $rechazada = true; }
    verificarDistribucion($rechazada, 'rechaza aulas ajenas al horario');
    $db->rollBack();
    echo "RESULTADO: distribución de huella de profesor verificada.\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    fwrite(STDERR, "FALLA: {$e->getMessage()}\n");
    exit(1);
}
