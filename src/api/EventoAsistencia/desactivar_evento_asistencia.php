<?php
require_once '../../config/db.php';
require_once '../../classes/EventoAsistencia.php';

$db = (new Database())->getConnection();
$evento = new EventoAsistencia($db);

$evento->id_evento = $_POST['id_evento'] ?? null;

echo $evento->desactivar() ? "✅ Evento desactivado" : "❌ Error";
?>