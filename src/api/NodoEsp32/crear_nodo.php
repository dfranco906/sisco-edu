<?php
require_once '../../config/db.php';
require_once '../../classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->node_id = $_POST['node_id'] ?? null;
$nodo->id_aula = $_POST['id_aula'] ?? null;
$nodo->tipo = $_POST['tipo'] ?? null;

echo $nodo->crear()
    ? "✅ Nodo ESP32 creado correctamente"
    : "❌ Error al crear nodo";
?>