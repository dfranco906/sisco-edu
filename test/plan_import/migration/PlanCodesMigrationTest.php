<?php
declare(strict_types=1);

$dbName = 'sisco_plan_import_test_'.getmypid().'_'.random_int(1000, 9999);
if (preg_match('/^sisco_plan_import_test_[0-9_]+$/', $dbName) !== 1) throw new RuntimeException('Nombre temporal no seguro.');
$pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$migration = dirname(__DIR__, 3).'/database/migrations/20260903_planificacion_codigos_jerarquicos.sql';
$failures = [];

function migrationAssert(bool $condition, string $message): void { global $failures; if (!$condition) $failures[] = $message; }

try {
    $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $pdo->exec("USE `{$dbName}`");
    $pdo->exec('CREATE TABLE plan_unidades (id_unidad INT AUTO_INCREMENT PRIMARY KEY,id_plan INT NOT NULL,nombre VARCHAR(255) NOT NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE plan_temas (id_tema INT AUTO_INCREMENT PRIMARY KEY,id_capacidad INT NOT NULL,titulo VARCHAR(255) NOT NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE plan_indicadores (id_indicador INT AUTO_INCREMENT PRIMARY KEY,id_tema INT NOT NULL,descripcion TEXT NOT NULL) ENGINE=InnoDB');
    $pdo->exec("INSERT INTO plan_unidades (id_plan,nombre) VALUES (1,'Unidad existente')");
    $pdo->exec("INSERT INTO plan_temas (id_capacidad,titulo) VALUES (1,'Tema existente')");
    $pdo->exec("INSERT INTO plan_indicadores (id_tema,descripcion) VALUES (1,'Indicador existente')");

    $sql = (string) file_get_contents($migration);
    $active = preg_replace('/^\s*--.*$/m', '', $sql) ?? '';
    $statements = array_values(array_filter(array_map('trim', explode(';', $active))));
    migrationAssert(count($statements) === 3, 'la migración debe contener exactamente tres ALTER activos');
    foreach ($statements as $statement) $pdo->exec($statement);
    foreach ($statements as $statement) $pdo->exec($statement);

    foreach (['plan_unidades','plan_temas','plan_indicadores'] as $table) {
        $stmt = $pdo->prepare('SELECT DATA_TYPE,CHARACTER_MAXIMUM_LENGTH,IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=:db AND TABLE_NAME=:table AND COLUMN_NAME=\'codigo\'');
        $stmt->execute([':db'=>$dbName, ':table'=>$table]);
        $column = $stmt->fetch(PDO::FETCH_ASSOC);
        migrationAssert($column !== false, $table.' debe tener codigo');
        migrationAssert(($column['DATA_TYPE'] ?? null) === 'varchar' && (int) ($column['CHARACTER_MAXIMUM_LENGTH'] ?? 0) === 32, $table.'.codigo debe ser VARCHAR(32)');
        migrationAssert(($column['IS_NULLABLE'] ?? null) === 'YES', $table.'.codigo debe aceptar NULL');
        migrationAssert((int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() === 1, $table.' debe conservar su fila');
        migrationAssert($pdo->query("SELECT codigo FROM {$table} LIMIT 1")->fetchColumn() === null, $table.' existente debe quedar con codigo NULL');
    }

    foreach (['plan_indicadores','plan_temas','plan_unidades'] as $table) $pdo->exec("ALTER TABLE {$table} DROP COLUMN IF EXISTS codigo");
    foreach (['plan_unidades','plan_temas','plan_indicadores'] as $table) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=:db AND TABLE_NAME=:table AND COLUMN_NAME=\'codigo\'');
        $stmt->execute([':db'=>$dbName, ':table'=>$table]);
        migrationAssert((int) $stmt->fetchColumn() === 0, 'rollback debe quitar sólo '.$table.'.codigo');
        migrationAssert((int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn() === 1, 'rollback debe conservar filas de '.$table);
    }
} finally {
    $pdo->exec("DROP DATABASE IF EXISTS `{$dbName}`");
}

if ($failures) {
    fwrite(STDERR, "FALLAS MIGRACIÓN CÓDIGOS:\n- ".implode("\n- ", $failures)."\n");
    exit(1);
}
echo "OK | PlanCodesMigrationTest\n";
