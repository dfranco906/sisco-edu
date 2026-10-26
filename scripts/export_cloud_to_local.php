<?php
/**
 * Exportador completo del esquema y datos de Supabase Cloud a supabase/migrations y supabase/seed.sql
 */
require_once __DIR__ . '/../src/config/db.php';

try {
    $db = (new Database())->getConnection();
    echo "Conectado a la base de datos cloud exitosamente.\n";

    $supabaseDir = __DIR__ . '/../supabase';
    $migrationsDir = $supabaseDir . '/migrations';
    if (!is_dir($migrationsDir)) {
        mkdir($migrationsDir, 0777, true);
    }

    // 1. Obtener todas las tablas en orden de dependencia
    $tablas = [
        // Tablas base independientes
        'usuarios',
        'turnos',
        'niveles_academicos',
        'bachilleratos',
        'aulas',
        'grados',
        'materias',
        'profesores',
        'estudiantes',
        'tutores',
        'estudiante_tutores',
        'coordinador_grados',
        'matriculas',
        'asignaciones',
        'asignacion_docente',
        'horarios',
        'nodos_esp32',
        'configuraciones_sistema',
        'configuracion_informes',
        'instrumentos_evaluativos',
        'procedimientos_evaluativos',
        'planes_anuales',
        'plan_unidades',
        'plan_capacidades',
        'plan_temas',
        'plan_indicadores',
        'plan_tema_programacion',
        'plan_tema_instrumentos',
        'plan_tema_procedimientos',
        'clases_diarias',
        'clase_diaria_indicadores',
        'registros_anecdoticos',
        'huellas_templates',
        'solicitudes_huella',
        'sync_biometrica',
        'aula_huellas_sync',
        'asistencias',
        'asistencias_estudiantes',
        'asistencias_profesores',
        'eventos_asistencia',
        'biometric_mapping'
    ];

    // Exportar DDL
    $ddlSql = "-- ============================================================================\n";
    $ddlSql .= "-- SISCO-EDU: Esquema Completo PostgreSQL / Supabase Baseline\n";
    $ddlSql .= "-- Generado automaticamente: " . date('Y-m-d H:i:s') . "\n";
    $ddlSql .= "-- ============================================================================\n\n";
    $ddlSql .= "CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";\n";
    $ddlSql .= "CREATE EXTENSION IF NOT EXISTS \"pgcrypto\";\n\n";

    // Extraer DDL de cada tabla
    foreach ($tablas as $tabla) {
        // Verificar si la tabla existe
        $check = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
        $check->execute([$tabla]);
        if (!$check->fetch()) continue;

        $ddlSql .= "-- --------------------------------------------------------\n";
        $ddlSql .= "-- Tabla: public.{$tabla}\n";
        $ddlSql .= "-- --------------------------------------------------------\n";
        $ddlSql .= "CREATE TABLE IF NOT EXISTS public.{$tabla} (\n";

        // Obtener columnas
        $colsStmt = $db->prepare("
            SELECT column_name, data_type, udt_name, character_maximum_length, 
                   is_nullable, column_default
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = ?
            ORDER BY ordinal_position
        ");
        $colsStmt->execute([$tabla]);
        $columnas = $colsStmt->fetchAll(PDO::FETCH_ASSOC);

        $lineasCols = [];
        foreach ($columnas as $col) {
            $nombre = $col['column_name'];
            $tipo = $col['udt_name'];
            
            // Mapeo amigable de tipos Postgres
            if ($tipo === 'varchar') {
                $tipoSql = "VARCHAR(" . ($col['character_maximum_length'] ?: 255) . ")";
            } elseif ($tipo === 'bpchar') {
                $tipoSql = "CHAR(" . ($col['character_maximum_length'] ?: 1) . ")";
            } elseif ($tipo === 'int4') {
                $tipoSql = "INTEGER";
            } elseif ($tipo === 'int2') {
                $tipoSql = "SMALLINT";
            } elseif ($tipo === 'int8') {
                $tipoSql = "BIGINT";
            } elseif ($tipo === 'bool') {
                $tipoSql = "BOOLEAN";
            } elseif ($tipo === 'timestamptz') {
                $tipoSql = "TIMESTAMPTZ";
            } elseif ($tipo === 'timestamp') {
                $tipoSql = "TIMESTAMP";
            } elseif ($tipo === 'numeric') {
                $tipoSql = "NUMERIC";
            } else {
                $tipoSql = strtoupper($tipo);
            }

            // SERIAL / Secuencia en default
            $default = $col['column_default'];
            $nullable = ($col['is_nullable'] === 'NO') ? 'NOT NULL' : '';

            $colDef = "    \"{$nombre}\" {$tipoSql}";
            if ($default !== null) {
                $colDef .= " DEFAULT {$default}";
            }
            if ($nullable !== '') {
                $colDef .= " {$nullable}";
            }
            $lineasCols[] = $colDef;
        }

        // Obtener Clave Primaria
        $pkStmt = $db->prepare("
            SELECT c.column_name
            FROM information_schema.table_constraints tc 
            JOIN information_schema.constraint_column_usage c ON c.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_schema = 'public' AND tc.table_name = ?
        ");
        $pkStmt->execute([$tabla]);
        $pks = $pkStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($pks)) {
            $pkCols = implode('", "', $pks);
            $lineasCols[] = "    PRIMARY KEY (\"{$pkCols}\")";
        }

        $ddlSql .= implode(",\n", $lineasCols) . "\n);\n\n";

        // RLS
        $ddlSql .= "ALTER TABLE public.{$tabla} ENABLE ROW LEVEL SECURITY;\n";
        $ddlSql .= "DO $$ BEGIN\n";
        $ddlSql .= "    IF NOT EXISTS (SELECT 1 FROM pg_policies WHERE tablename = '{$tabla}' AND policyname = 'acceso_total_{$tabla}') THEN\n";
        $ddlSql .= "        CREATE POLICY \"acceso_total_{$tabla}\" ON public.{$tabla} FOR ALL USING (true) WITH CHECK (true);\n";
        $ddlSql .= "    END IF;\n";
        $ddlSql .= "END $$;\n\n";
    }

    // Regla Biometrica CHECK obligatoria
    $ddlSql .= "-- Contrato Biometrico Estricto de 1536 bytes binarios (3072 HEX)\n";
    $ddlSql .= "DO $$ BEGIN\n";
    $ddlSql .= "    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_huella_template_activa_1536') THEN\n";
    $ddlSql .= "        ALTER TABLE public.huellas_templates ADD CONSTRAINT chk_huella_template_activa_1536\n";
    $ddlSql .= "        CHECK (activo != 1 OR length(huella_template) = 3072);\n";
    $ddlSql .= "    END IF;\n";
    $ddlSql .= "END $$;\n\n";

    // Guardar migración baseline
    $migrationPath = $migrationsDir . '/20261026000000_sisco_edu_baseline.sql';
    file_put_contents($migrationPath, $ddlSql);
    echo "Migracion baseline generada: {$migrationPath}\n";

    // 2. Exportar SEED SQL (datos de tablas maestras)
    $seedSql = "-- ============================================================================\n";
    $seedSql .= "-- SISCO-EDU: Seed Inicial de Datos para Trabajo Offline\n";
    $seedSql .= "-- Generado automaticamente: " . date('Y-m-d H:i:s') . "\n";
    $seedSql .= "-- ============================================================================\n\n";

    $seedTables = [
        'turnos',
        'niveles_academicos',
        'bachilleratos',
        'usuarios',
        'aulas',
        'grados',
        'materias',
        'profesores',
        'estudiantes',
        'matriculas',
        'coordinador_grados',
        'asignacion_docente',
        'horarios',
        'nodos_esp32',
        'configuraciones_sistema',
        'configuracion_informes',
        'instrumentos_evaluativos',
        'procedimientos_evaluativos',
        'planes_anuales',
        'plan_unidades',
        'plan_capacidades',
        'plan_temas',
        'plan_indicadores',
        'plan_tema_programacion',
        'plan_tema_instrumentos',
        'plan_tema_procedimientos',
        'clases_diarias',
        'clase_diaria_indicadores',
        'huellas_templates',
        'asistencias',
        'asistencias_estudiantes',
        'asistencias_profesores',
        'eventos_asistencia'
    ];

    foreach ($seedTables as $tabla) {
        $check = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
        $check->execute([$tabla]);
        if (!$check->fetch()) continue;

        $rows = $db->query("SELECT * FROM public.{$tabla}")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) continue;

        $seedSql .= "-- Datos para: public.{$tabla} (" . count($rows) . " filas)\n";

        // Obtener PK para ON CONFLICT
        $pkStmt = $db->prepare("
            SELECT c.column_name
            FROM information_schema.table_constraints tc 
            JOIN information_schema.constraint_column_usage c ON c.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_schema = 'public' AND tc.table_name = ?
        ");
        $pkStmt->execute([$tabla]);
        $pks = $pkStmt->fetchAll(PDO::FETCH_COLUMN);
        $pkClause = !empty($pks) ? " ON CONFLICT (\"" . implode('", "', $pks) . "\") DO NOTHING" : "";

        $cols = array_keys($rows[0]);
        $colsEscaped = '"' . implode('", "', $cols) . '"';

        foreach ($rows as $r) {
            $vals = [];
            foreach ($cols as $c) {
                $val = $r[$c];
                if ($val === null) {
                    $vals[] = 'NULL';
                } elseif (is_bool($val)) {
                    $vals[] = $val ? 'TRUE' : 'FALSE';
                } elseif (is_numeric($val) && !is_string($val)) {
                    $vals[] = $val;
                } else {
                    $vals[] = "'" . str_replace("'", "''", (string)$val) . "'";
                }
            }
            $seedSql .= "INSERT INTO public.{$tabla} ({$colsEscaped}) VALUES (" . implode(', ', $vals) . "){$pkClause};\n";
        }

        // Actualizar secuencia de la clave primaria si aplica
        if (count($pks) === 1) {
            $pkCol = $pks[0];
            $seedSql .= "SELECT setval(pg_get_serial_sequence('public.{$tabla}', '{$pkCol}'), coalesce(max(\"{$pkCol}\"), 1), true) FROM public.{$tabla};\n\n";
        } else {
            $seedSql .= "\n";
        }
    }

    $seedPath = $supabaseDir . '/seed.sql';
    file_put_contents($seedPath, $seedSql);
    echo "Seed SQL generado exitosamente: {$seedPath} (" . strlen($seedSql) . " bytes)\n";

} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    exit(1);
}
