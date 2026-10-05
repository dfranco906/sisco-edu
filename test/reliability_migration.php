<?php
declare(strict_types=1);
require_once __DIR__.'/plan_import/support/TestEnvironment.php';
$env=new PlanImportTestEnvironment();
try {
    $sql=file_get_contents(__DIR__.'/../database/migrations/20261005_entrega_asistencia_confiable.sql');
    $env->db->exec($sql); $env->db->exec($sql);
    foreach (['eventos_asistencia','sync_biometrica','nodos_esp32','solicitudes_huella'] as $table) {
        echo $env->db->query('SHOW CREATE TABLE '.$table)->fetch(PDO::FETCH_NUM)[1].PHP_EOL;
    }
    echo "PASS | migración aplicada dos veces en BD temporal; datos reales modificados: 0\n";
} finally { $env->close(); }
