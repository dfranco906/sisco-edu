<?php
require_once '../../config/db.php';
require_once '../../classes/EventoAsistencia.php';

$db = (new Database())->getConnection();
$evento = new EventoAsistencia($db);

$evento->id_evento = $_POST['id_evento'] ?? null;
$evento->user_id_global = $_POST['user_id_global'] ?? null;
$evento->id_aula = $_POST['id_aula'] ?? null;
$evento->timestamp_evento = $_POST['timestamp_evento'] ?? time();
$evento->origen_node_id = $_POST['origen_node_id'] ?? null;
$evento->sincronizado = $_POST['sincronizado'] ?? 1;

echo $evento->actualizar() ? "✅ Evento actualizado" : "❌ Error";
?>