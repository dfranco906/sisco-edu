<?php
require_once '../../config/db.php';
require_once '../../classes/NodoEsp32.php';

$db = (new Database())->getConnection();
$nodo = new NodoEsp32($db);

$nodo->id_nodo = $_POST['id_nodo'] ?? null;

echo $nodo->desactivar()
    ? "✅ Nodo ESP32 desactivado correctamente"
    : "❌ Error al desactivar nodo";
?>