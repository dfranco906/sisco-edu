<?php
require_once '../config/db.php';
require_once '../classes/HuellaTemplate.php';

$db = (new Database())->getConnection();
$huella = new HuellaTemplate($db);

$huella->id_huella = $_POST['id_huella'] ?? null;

echo $huella->desactivar()
    ? "✅ Huella desactivada correctamente"
    : "❌ Error al desactivar huella";
?>