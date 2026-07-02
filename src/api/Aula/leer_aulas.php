<?php
require_once '../../config/db.php';
require_once '../../classes/Aula.php';

$db = (new Database())->getConnection();
$aula = new Aula($db);

$stmt = $aula->leer();
$aulas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($aulas);
?>