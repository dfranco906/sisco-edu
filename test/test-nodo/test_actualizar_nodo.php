<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->id_nodo = 1;
$nodo->node_id = "ESP32_AULA_1";
$nodo->room_id = "AULA_1";
$nodo->tipo = "AULA";
$nodo->estado = "online";

echo $nodo->actualizar() ? "✅ Nodo actualizado" : "❌ Error";
?>