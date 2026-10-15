<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/EventoAsistencia.php';

$db = (new Database())->getConnection();
$evento = new EventoAsistencia($db);

$evento->id_evento = 1;
$evento->user_id_global = "USER_001";
$evento->room_id = "AULA_1";
$evento->timestamp_evento = time();
$evento->origen_node_id = "ESP32_AULA_1";
$evento->sincronizado = 1;

echo $evento->actualizar()
    ? "✅ Evento actualizado"
    : "❌ Error";
?>