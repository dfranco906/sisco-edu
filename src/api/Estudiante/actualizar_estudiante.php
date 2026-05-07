<?php
require_once '../config/db.php';
require_once '../classes/Estudiante.php';

$db = (new Database())->getConnection();
$estudiante = new Estudiante($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $estudiante->id_estudiante = $_POST['id_estudiante'] ?? null;
    $estudiante->nombre = $_POST['nombre'] ?? null;
    $estudiante->apellido = $_POST['apellido'] ?? null;
    $estudiante->cedula_identidad = $_POST['cedula_identidad'] ?? null;
    $estudiante->huella_id = $_POST['huella_id'] ?? null;

    if (!empty($estudiante->id_estudiante)) {

        if ($estudiante->actualizar()) {
            echo "✅ Estudiante actualizado";
        } else {
            echo "❌ Error al actualizar";
        }

    } else {
        echo "❌ ID requerido";
    }

} else {
    echo "⛔ Método no permitido";
}
?>