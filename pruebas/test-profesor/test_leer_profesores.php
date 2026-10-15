<?php

require_once '../src/config/db.php';
require_once '../src/classes/Profesor.php';

echo "<h2>TEST LEER PROFESORES</h2>";

$database = new Database();
$db = $database->getConnection();

$profesor = new Profesor($db);

$stmt = $profesor->leer();
$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($profesores) > 0){

    echo "<table border='1' cellpadding='8'>";
    echo "<tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellido</th>
            <th>Cedula</th>
            <th>Huella</th>
            <th>Fecha</th>
          </tr>";

    foreach($profesores as $row){
        echo "<tr>";
        echo "<td>".$row['id_profesor']."</td>";
        echo "<td>".$row['nombre']."</td>";
        echo "<td>".$row['apellido']."</td>";
        echo "<td>".$row['cedula_identidad']."</td>";
        echo "<td>".$row['huella_id']."</td>";
        echo "<td>".$row['fecha_registro']."</td>";
        echo "</tr>";
    }

    echo "</table>";

}else{
    echo "No hay profesores cargados.";
}
?>