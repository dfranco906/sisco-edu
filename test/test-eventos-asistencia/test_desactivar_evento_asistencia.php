<?php
require_once __DIR__ . '/../../src/config/db.php';
require_once __DIR__ . '/../../src/classes/EventoAsistencia.php';

$db = (new Database())->getConnection();
$evento = new EventoAsistencia($db);

$evento->id_evento = 1;

echo $evento->desactivar() ? "✅ Evento desactivado" : "❌ Error";
?>