<?php
require_once '../../config/db.php';
require_once '../../classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->node_id = $_POST['node_id'] ?? null;

echo $nodo->heartbeat()
    ? "✅ Heartbeat recibido"
    : "❌ Nodo no encontrado o inactivo";
?>