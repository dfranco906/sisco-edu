<?php
require_once __DIR__.'/../../config/db.php';
require_once __DIR__.'/../../config/device_auth.php';
require_once __DIR__.'/../../classes/GatewayRoutes.php';
requireDevice('GET');
try {
    echo json_encode(['status'=>'success','data'=>GatewayRoutes::load((new Database())->getConnection())],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('rutas_aulas_gateway: '.$e->getMessage()); http_response_code(409);
    echo json_encode(['status'=>'error','message'=>'Rutas inválidas o no disponibles; conservar cache']);
}
