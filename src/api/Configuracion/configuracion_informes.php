<?php
require_once __DIR__ . '/../../config/api_auth.php';
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../classes/PlanificacionPedagogica.php';
requerirMetodo(['GET','POST']);
$usuario=usuarioActual(['SuperAdmin','Administracion']);
try {
    $db=(new Database())->getConnection();
    if($_SERVER['REQUEST_METHOD']==='GET'){
        $fila=$db->query("SELECT id_configuracion,membrete_path,updated_at FROM configuracion_informes WHERE activo=1 ORDER BY updated_at DESC,id_configuracion DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC)?:null;
        responderJson(['success'=>true,'status'=>'success','data'=>$fila]);
    }
    if(empty($_FILES['membrete'])||!is_array($_FILES['membrete']))throw new PedagogiaException('Seleccione una imagen.',422);
    $archivo=$_FILES['membrete'];
    if(($archivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new PedagogiaException('La carga de la imagen no se completo.',422);
    if((int)$archivo['size']>2*1024*1024)throw new PedagogiaException('El membrete no puede superar 2 MB.',422);
    $extension=strtolower(pathinfo((string)$archivo['name'],PATHINFO_EXTENSION));
    $permitidos=['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'];
    $finfo=new finfo(FILEINFO_MIME_TYPE);$mime=$finfo->file((string)$archivo['tmp_name']);
    if(!isset($permitidos[$extension])||$permitidos[$extension]!==$mime)throw new PedagogiaException('Solo se permiten imagenes PNG o JPG/JPEG validas.',422);
    $directorio=dirname(__DIR__,3).'/public/uploads/informes';
    if(!is_dir($directorio)&&!mkdir($directorio,0755,true)&&!is_dir($directorio))throw new RuntimeException('No se pudo preparar el directorio de membretes.');
    $nombre='membrete_'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'.'.($extension==='jpeg'?'jpg':$extension);
    if(!move_uploaded_file((string)$archivo['tmp_name'],$directorio.'/'.$nombre))throw new RuntimeException('No se pudo guardar el archivo subido.');
    $ruta='public/uploads/informes/'.$nombre;
    $db->beginTransaction();
    try{$db->exec('UPDATE configuracion_informes SET activo=0 WHERE activo=1');$stmt=$db->prepare('INSERT INTO configuracion_informes (membrete_path,activo,updated_by) VALUES (:ruta,1,:usuario)');$stmt->execute([':ruta'=>$ruta,':usuario'=>$usuario['id_usuario']]);$db->commit();}
    catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw$e;}
    responderJson(['success'=>true,'status'=>'success','message'=>'Membrete actualizado.','data'=>['membrete_path'=>$ruta]],201);
} catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}
catch(Throwable $e){error_log('configuracion_informes: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo actualizar el membrete.'],500);}
?>
