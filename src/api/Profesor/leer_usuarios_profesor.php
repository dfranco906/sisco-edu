<?php
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../../config/api_auth.php';
usuarioActual(['SuperAdmin','Administracion']);
try {
    $db=(new Database())->getConnection();
    $data=[['id_usuario'=>0,'descripcion'=>'Sin vincular']];
    $stmt=$db->query("SELECT u.id_usuario,CONCAT(u.usuario,' - ',u.nombre,' ',u.apellido) descripcion
        FROM usuarios u LEFT JOIN profesores p ON p.id_usuario=u.id_usuario
        WHERE u.rol='Profesor' AND u.activo=1 AND p.id_profesor IS NULL ORDER BY u.apellido,u.nombre,u.usuario");
    $data=array_merge($data,$stmt->fetchAll(PDO::FETCH_ASSOC));
    echo json_encode(['success'=>true,'status'=>'success','data'=>$data],JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    error_log('leer_usuarios_profesor: '.$e->getMessage());http_response_code(500);
    echo json_encode(['success'=>false,'status'=>'error','message'=>'No se pudieron cargar las cuentas.','data'=>[]]);
}
?>
