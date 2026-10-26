<?php
// scripts/switch_env.php

$target = $argv[1] ?? '';
$target = strtolower(trim($target));

$configFile = __DIR__ . '/../config.local.php';
if (!file_exists($configFile)) {
    echo "El archivo config.local.php no existe. Creando desde template...\n";
    copy(__DIR__ . '/../config.local.template.php', $configFile);
}

$content = file_get_contents($configFile);

if (empty($target)) {
    // Detectar modo actual
    if (preg_match("/\\\$modoActivo\s*=\s*'([^']+)'/", $content, $m)) {
        echo "MODO ACTUAL: " . strtoupper($m[1]) . "\n";
    } else {
        echo "MODO ACTUAL: NO DETERMINADO (usando defaults)\n";
    }
    echo "Uso: switch_env.bat [local|cloud]\n";
    exit(0);
}

if ($target !== 'local' && $target !== 'cloud') {
    echo "Modo invalido: '{$target}'. Use 'local' o 'cloud'.\n";
    exit(1);
}

// Reemplazar $modoActivo
$nuevoContent = preg_replace("/\\\$modoActivo\s*=\s*'[^']+'/", "\$modoActivo = '{$target}'", $content);
file_put_contents($configFile, $nuevoContent);

echo "============================================================================\n";
echo "[OK] Entorno cambiado exitosamente a: " . strtoupper($target) . "\n";
if ($target === 'local') {
    echo "  - Conexión configurada a PostgreSQL Local (127.0.0.1:54322 / 5432)\n";
    echo "  - Operación 100% OFFLINE sin depender de Internet institucional\n";
} else {
    echo "  - Conexión configurada a Supabase Cloud (aws-0-us-east-1.pooler.supabase.com)\n";
    echo "  - Requiere conexión a Internet activa\n";
}
echo "============================================================================\n";
