<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->node_id = "ESP32_AULA_1";

echo $nodo->heartbeat() ? "✅ Heartbeat recibido" : "❌ Nodo no encontrado";
?>