<?php
// scripts/import_seed_local.php
/**
 * Inicializa la base de datos local (PostgreSQL o Supabase CLI) aplicando
 * la migración baseline y los datos iniciales (seed.sql).
 */
require_once __DIR__ . '/../src/config/app.php';

$configFile = __DIR__ . '/../config.local.php';
$config = file_exists($configFile) ? (include $configFile) : [];
$localDb = $config['LOCAL_DB'] ?? [
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => 54322,
    'DB_NAME' => 'postgres',
    'DB_USER' => 'postgres',
    'DB_PASS' => 'postgres',
    'DB_SSL'  => false,
];

$host = $localDb['DB_HOST'];
$port = $localDb['DB_PORT'];
$dbName = $localDb['DB_NAME'];
$user = $localDb['DB_USER'];
$pass = $localDb['DB_PASS'];
$ssl = !empty($localDb['DB_SSL']) ? 'require' : 'prefer';

echo "============================================================================\n";
echo "  SISCO-EDU: INICIALIZADOR DE BASE DE DATOS LOCAL OFFLINE\n";
echo "============================================================================\n";
echo "Conectando a PostgreSQL Local en {$host}:{$port} (BD: {$dbName})...\n";

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$ssl}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "[OK] Conexión establecida con PostgreSQL Local.\n\n";

    // 1. Aplicar Migración Baseline
    $migrationFile = __DIR__ . '/../supabase/migrations/20261026000000_sisco_edu_baseline.sql';
    if (!file_exists($migrationFile)) {
        throw new RuntimeException("Archivo de migración no encontrado: {$migrationFile}");
    }
    echo "1. Aplicando esquema DDL ({$migrationFile})...\n";
    $sqlDdl = file_get_contents($migrationFile);
    $pdo->exec($sqlDdl);
    echo "[OK] Esquema de 41 tablas creado exitosamente.\n\n";

    // 2. Aplicar Seed Data
    $seedFile = __DIR__ . '/../supabase/seed.sql';
    if (!file_exists($seedFile)) {
        throw new RuntimeException("Archivo seed no encontrado: {$seedFile}");
    }
    echo "2. Insertando datos semilla offline ({$seedFile})...\n";
    $sqlSeed = file_get_contents($seedFile);
    $pdo->exec($sqlSeed);
    echo "[OK] Datos semilla importados exitosamente.\n\n";

    // 3. Verificación
    $stmt = $pdo->query("SELECT count(*) FROM information_schema.tables WHERE table_schema = 'public'");
    $totalTablas = $stmt->fetchColumn();
    $stmtDoc = $pdo->query("SELECT count(*) FROM public.profesores");
    $totalDocentes = $stmtDoc->fetchColumn();
    $stmtAul = $pdo->query("SELECT count(*) FROM public.aulas");
    $totalAulas = $stmtAul->fetchColumn();

    echo "============================================================================\n";
    echo "[EXITO] BASE DE DATOS LOCAL LISTA PARA OPERAR 100% OFFLINE:\n";
    echo "  - Total tablas creadas : {$totalTablas}\n";
    echo "  - Profesores cargados  : {$totalDocentes}\n";
    echo "  - Aulas activas        : {$totalAulas}\n";
    echo "============================================================================\n";

} catch (Throwable $e) {
    echo "\n[ERROR] Falló la inicialización: " . $e->getMessage() . "\n";
    echo "\nSugerencias:\n";
    echo "  - Si usas Supabase CLI, asegúrate de haber ejecutado 'supabase start'.\n";
    echo "  - Si usas PostgreSQL nativo, verifica que el servicio esté iniciado en el puerto {$port}.\n";
    echo "  - Comprueba usuario y contraseña en config.local.php (LOCAL_DB).\n";
    exit(1);
}
