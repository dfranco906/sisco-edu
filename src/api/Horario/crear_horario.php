<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Horario.php';

$db = (new Database())->getConnection();
$horario = new Horario($db);

$horario->id_asignacion = $_POST['id_asignacion'] ?? null;
$horario->grado = $_POST['grado'] ?? null;
$horario->dia_semana = $_POST['dia_semana'] ?? null;
$horario->hora_inicio = $_POST['hora_inicio'] ?? null;
$horario->hora_fin = $_POST['hora_fin'] ?? null;
$horario->aula = $_POST['aula'] ?? null;

if (!$horario->id_asignacion || !$horario->grado || !$horario->dia_semana || !$horario->hora_inicio || !$horario->hora_fin || !$horario->aula) {
    echo json_encode([
        "status" => "error",
        "message" => "Datos incompletos"
    ]);
    exit;
}

$resultado = $horario->crear();

echo json_encode([
    "status" => $resultado ? "success" : "error",
    "message" => $resultado ? "Horario creado correctamente" : "Error al crear horario"
]);
?>