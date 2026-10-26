<?php
// scripts/sincronizar_nube_cli.php
require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/classes/SincronizadorNube.php';

try {
    $s = new SincronizadorNube();
    echo "Conectando con BD Local y Supabase Cloud...\n";
    $res = $s->sincronizarLocalHaciaNube();
    echo "\n>>> SINCRONIZACIÓN EXITOSA <<<\n\n";
    foreach ($res['resumen'] as $t => $cant) {
        if ($cant > 0) {
            echo "  - " . str_pad($t, 28) . ": " . $cant . " registros\n";
        }
    }
    if (!empty($res['detalles'])) {
        echo "\nDetalles:\n";
        foreach ($res['detalles'] as $d) {
            echo "  * {$d}\n";
        }
    }
    exit(0);
} catch (Throwable $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    exit(1);
}
