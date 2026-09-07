<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/biometria.php';
require_once __DIR__ . '/../../classes/DistribucionHuellaProfesor.php';
$db = (new Database())->getConnection();
$tipo = strtolower(trim($_POST['tipo_persona'] ?? $_POST['tipo_usuario'] ?? 'estudiante'));
$template = trim($_POST['template'] ?? '');
$crcRecibido = strtolower(trim($_POST['crc32'] ?? ''));
$idRecibido = filter_input(INPUT_POST, 'id_persona', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id_estudiante', FILTER_VALIDATE_INT);
$userIdRecibido = trim($_POST['user_id_global'] ?? '');
$bytesRecibidos = filter_input(INPUT_POST, 'bytes', FILTER_VALIDATE_INT);
if (!in_array($tipo, ['estudiante','profesor'], true)
    || !es_template_huella_hex_valido($template)
    || ($bytesRecibidos !== null && $bytesRecibidos !== false && $bytesRecibidos !== HUELLA_TEMPLATE_BYTES)) {
    http_response_code(422); echo json_encode(['status'=>'error','message'=>'Datos o template HEX de 1536 bytes invalido']); exit;
}
$templateBinario = hex2bin($template);
$crcCalculado = $templateBinario === false ? '' : strtolower(hash('crc32b', $templateBinario));
if (preg_match('/\A[0-9a-f]{8}\z/D', $crcRecibido) !== 1
    || $crcCalculado === ''
    || !hash_equals($crcCalculado, $crcRecibido)) {
    http_response_code(422); echo json_encode(['status'=>'error','message'=>'CRC32 del template no coincide con el informado por el registrador']); exit;
}
try {
    $tabla = $tipo === 'estudiante' ? 'estudiantes' : 'profesores'; $idCampo = $tipo === 'estudiante' ? 'id_estudiante' : 'id_profesor';
    if ($idRecibido) { $stmt=$db->prepare("SELECT {$idCampo},user_id_global" . ($tipo==='estudiante' ? ',id_grado' : '') . " FROM {$tabla} WHERE {$idCampo}=:id AND activo=1 LIMIT 1"); $stmt->execute([':id'=>$idRecibido]); }
    elseif ($userIdRecibido !== '') { $stmt=$db->prepare("SELECT {$idCampo},user_id_global" . ($tipo==='estudiante' ? ',id_grado' : '') . " FROM {$tabla} WHERE user_id_global=:uid AND activo=1 LIMIT 1"); $stmt->execute([':uid'=>$userIdRecibido]); }
    else { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Falta identificador de persona']); exit; }
    $persona=$stmt->fetch(PDO::FETCH_ASSOC);
    if (!$persona || !$persona['user_id_global']) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Persona no encontrada o sin user_id_global']); exit; }
    $idPersona=(int)$persona[$idCampo];
    $servicioDistribucion = new DistribucionHuellaProfesor($db);
    $idAula = filter_input(INPUT_POST,'id_aula',FILTER_VALIDATE_INT);
    $idsAula = $servicioDistribucion->normalizarIdsAula($_POST['id_aulas'] ?? []);
    if ($tipo === 'estudiante') {
        $stmtAula=$db->prepare("SELECT id_aula FROM grados WHERE id_grado=:grado AND activo=1 LIMIT 1"); $stmtAula->execute([':grado'=>$persona['id_grado']]); $idAula=(int)$stmtAula->fetchColumn();
        $idsAula = $idAula ? [$idAula] : [];
    } elseif (!$idsAula && $idAula) {
        $idsAula = [(int) $idAula];
    } elseif (!$idsAula) {
        // Compatibilidad con clientes anteriores: usar la primera aula programada.
        $stmtAula=$db->prepare("SELECT h.id_aula FROM horarios h INNER JOIN asignacion_docente ad ON ad.id_asignacion=h.id_asignacion WHERE ad.id_profesor=:profesor AND h.activo=1 AND h.id_aula IS NOT NULL ORDER BY h.id_horario LIMIT 1");
        $stmtAula->execute([':profesor'=>$idPersona]); $idAula=(int)$stmtAula->fetchColumn(); $idsAula=$idAula?[$idAula]:[];
    }
    if (!$idsAula) { http_response_code(422); echo json_encode(['status'=>'error','message'=>'Seleccione al menos un aula para sincronizar']); exit; }
    if ($tipo === 'profesor' && count($servicioDistribucion->aulasValidas($idPersona, $idsAula)) !== count($idsAula)) {
        http_response_code(422); echo json_encode(['status'=>'error','message'=>'Seleccione solamente aulas activas incluidas en el horario del profesor']); exit;
    }
    $db->beginTransaction();
    $db->prepare("INSERT INTO huellas_templates (user_id_global,{$idCampo},fingerprint_data,formato,pendiente_sync,activo) VALUES (:uid,:id,:data,'HEX',1,1)")
       ->execute([':uid'=>$persona['user_id_global'],':id'=>$idPersona,':data'=>strtolower($template)]);
    $idHuella=(int)$db->lastInsertId();
    if ($tipo === 'profesor') {
        $distribucion = $servicioDistribucion->encolar($idHuella, $idPersona, $idsAula);
        $syncs = $distribucion['creadas'];
        $aulas = $distribucion['aulas'];
    } else {
        $db->prepare("INSERT INTO sync_biometrica (id_huella,id_aula,estado,intentos) VALUES (:huella,:aula,'PENDIENTE',0)")
           ->execute([':huella'=>$idHuella,':aula'=>$idsAula[0]]);
        $syncs = [['id_sync'=>(int)$db->lastInsertId(),'id_aula'=>$idsAula[0],'estado'=>'PENDIENTE']];
        $aulas = [['id_aula'=>$idsAula[0]]];
    }
    $db->commit();
    echo json_encode([
        'status'=>'success',
        'message'=>'Huella guardada y preparada para '.count($syncs).' aula(s)',
        'id_huella'=>$idHuella,
        'id_sync'=>$syncs[0]['id_sync'] ?? null,
        'id_aula'=>$syncs[0]['id_aula'] ?? null,
        'ids_sync'=>array_column($syncs,'id_sync'),
        'aulas'=>$aulas,
        'crc32'=>$crcCalculado,
        'gateway_avisado'=>DistribucionHuellaProfesor::avisarGateway()
    ]);
} catch(InvalidArgumentException $e) {
    if($db->inTransaction()) $db->rollBack();
    http_response_code(422); echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
} catch(Throwable $e) {
    if($db->inTransaction()) $db->rollBack();
    error_log('guardar_template: '.$e->getMessage());
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Error al guardar template']);
}
