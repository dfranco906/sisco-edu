<?php
require_once '../../config/db.php';
require_once '../../classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->id_nodo = $_POST['id_nodo'] ?? null;
$nodo->node_id = $_POST['node_id'] ?? null;
$nodo->room_id = $_POST['room_id'] ?? null;
$nodo->tipo = $_POST['tipo'] ?? null;
$nodo->estado = $_POST['estado'] ?? null;

echo $nodo->actualizar()
    ? "✅ Nodo ESP32 actualizado correctamente"
    : "❌ Error al actualizar nodo";
?>