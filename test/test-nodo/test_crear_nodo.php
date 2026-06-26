<?php
require_once '../../src/config/db.php';
require_once '../../src/classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->node_id = "ESP32_AULA_1";
$nodo->room_id = "AULA_1";
$nodo->tipo = "AULA";

echo $nodo->crear() ? "✅ Nodo creado" : "❌ Error";
?>