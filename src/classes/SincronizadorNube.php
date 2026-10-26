<?php
// src/classes/SincronizadorNube.php

require_once __DIR__ . '/../config/app.php';

class SincronizadorNube {
    private ?PDO $pdoLocal = null;
    private ?PDO $pdoCloud = null;
    private array $configLocal = [];
    private array $configCloud = [];
    private array $logSync = [];

    public function __construct() {
        $localConfigFile = __DIR__ . '/../../config.local.php';
        $config = file_exists($localConfigFile) ? (include $localConfigFile) : [];

        // Leer configuraciones de ambos perfiles
        $this->configLocal = $config['LOCAL_DB'] ?? [
            'DB_DRIVER' => 'pgsql',
            'DB_HOST'   => '127.0.0.1',
            'DB_PORT'   => 5432,
            'DB_NAME'   => 'postgres',
            'DB_USER'   => 'postgres',
            'DB_PASS'   => 'postgres',
            'DB_SSL'    => false,
        ];

        $this->configCloud = $config['CLOUD_DB'] ?? [
            'DB_DRIVER' => 'pgsql',
            'DB_HOST'   => 'aws-0-us-east-1.pooler.supabase.com',
            'DB_PORT'   => 6543,
            'DB_NAME'   => 'postgres',
            'DB_USER'   => '',
            'DB_PASS'   => '',
            'DB_SSL'    => true,
        ];
    }

    /**
     * Establece conexión con ambas bases de datos (Local y Cloud)
     */
    public function conectarAmbas(): void {
        $this->conectarLocal();
        $this->conectarCloud();
    }

    public function conectarLocal(): PDO {
        if ($this->pdoLocal !== null) return $this->pdoLocal;

        try {
            $host = $this->configLocal['DB_HOST'] ?? '127.0.0.1';
            $port = $this->configLocal['DB_PORT'] ?? 54322;
            $dbName = $this->configLocal['DB_NAME'] ?? 'postgres';
            $user = $this->configLocal['DB_USER'] ?? 'postgres';
            $pass = $this->configLocal['DB_PASS'] ?? 'postgres';
            $ssl = !empty($this->configLocal['DB_SSL']) ? 'require' : 'prefer';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$ssl}";
            $this->pdoLocal = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            return $this->pdoLocal;
        } catch (Throwable $e) {
            error_log("Error al conectar con BD Local: " . $e->getMessage());
            throw new RuntimeException("No se pudo conectar a la Base de Datos Local en {$host}:{$port}. Verifica que Supabase Local o PostgreSQL esté iniciado.");
        }
    }

    public function conectarCloud(): PDO {
        if ($this->pdoCloud !== null) return $this->pdoCloud;

        try {
            $host = $this->configCloud['DB_HOST'] ?? 'aws-0-us-east-1.pooler.supabase.com';
            $port = $this->configCloud['DB_PORT'] ?? 6543;
            $dbName = $this->configCloud['DB_NAME'] ?? 'postgres';
            $user = $this->configCloud['DB_USER'] ?? '';
            $pass = $this->configCloud['DB_PASS'] ?? '';
            $ssl = !empty($this->configCloud['DB_SSL']) ? 'require' : 'prefer';

            if (empty($user) || empty($pass) || $pass === 'TU_PASSWORD_DE_SUPABASE') {
                throw new RuntimeException("Credenciales de Supabase Cloud no configuradas. Por favor ingresa tus datos en config.local.php (sección 'CLOUD_DB').");
            }

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbName};sslmode={$ssl}";
            $this->pdoCloud = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
            ]);
            return $this->pdoCloud;
        } catch (Throwable $e) {
            error_log("Error al conectar con Supabase Cloud: " . $e->getMessage());
            throw new RuntimeException("No se pudo alcanzar la Base de Datos en la Nube ({$host}). Verifica tu conexión a Internet institucional.");
        }
    }

    /**
     * Sube todos los cambios y datos generados en local hacia la nube (UPSERT seguro)
     */
    public function sincronizarLocalHaciaNube(): array {
        $this->conectarAmbas();
        $this->logSync = [];
        $resumen = [];

        // Tablas a sincronizar hacia la nube en orden de dependencias
        $tablas = [
            'usuarios'                => 'id_usuario',
            'profesores'              => 'id_profesor',
            'estudiantes'             => 'id_estudiante',
            'huellas_templates'       => 'id_huella',
            'solicitudes_huella'      => 'id_solicitud',
            'sync_biometrica'         => 'id_sync',
            'aula_huellas_sync'       => 'id',
            'asistencias'             => 'id_asistencia',
            'asistencias_estudiantes' => 'id_asistencia_estudiante',
            'asistencias_profesores'  => 'id_asistencia_profesor',
            'eventos_asistencia'      => 'id_evento',
        ];

        foreach ($tablas as $tabla => $colPk) {
            $cant = $this->sincronizarTabla($tabla, $colPk);
            $resumen[$tabla] = $cant;
        }

        return [
            'status'     => 'success',
            'direccion'  => 'local_to_cloud',
            'timestamp'  => date('Y-m-d H:i:s'),
            'resumen'    => $resumen,
            'detalles'   => $this->logSync,
        ];
    }

    /**
     * Descarga y actualiza datos desde la nube hacia la base local
     */
    public function sincronizarNubeHaciaLocal(): array {
        $this->conectarAmbas();
        $this->logSync = [];
        $resumen = [];

        $tablas = [
            'usuarios'                   => 'id_usuario',
            'turnos'                     => 'id_turno',
            'niveles_academicos'         => 'id_nivel',
            'bachilleratos'              => 'id_bachillerato',
            'aulas'                      => 'id_aula',
            'grados'                     => 'id_grado',
            'materias'                   => 'id_materia',
            'profesores'                 => 'id_profesor',
            'estudiantes'                => 'id_estudiante',
            'tutores'                    => 'id_tutor',
            'estudiante_tutores'         => 'id_estudiante_tutor',
            'coordinador_grados'         => 'id_coordinador_grado',
            'matriculas'                 => 'id_matricula',
            'asignaciones'               => 'id_asignacion',
            'asignacion_docente'         => 'id_asignacion',
            'horarios'                   => 'id_horario',
            'nodos_esp32'                => 'id_nodo',
            'configuraciones_sistema'    => 'clave',
            'configuracion_informes'     => 'id_configuracion',
            'instrumentos_evaluativos'   => 'id_instrumento',
            'procedimientos_evaluativos' => 'id_procedimiento',
            'planes_anuales'             => 'id_plan',
            'plan_unidades'              => 'id_unidad',
            'plan_capacidades'           => 'id_capacidad',
            'plan_temas'                 => 'id_tema',
            'plan_indicadores'           => 'id_indicador',
            'plan_tema_programacion'     => 'id_programacion',
            'plan_tema_instrumentos'     => ['id_tema', 'id_instrumento'],
            'plan_tema_procedimientos'   => ['id_tema', 'id_procedimiento'],
            'clases_diarias'             => 'id_clase',
            'clase_diaria_indicadores'   => ['id_clase', 'id_indicador'],
            'registros_anecdoticos'      => 'id_registro',
            'huellas_templates'          => 'id_huella',
            'solicitudes_huella'         => 'id_solicitud',
            'sync_biometrica'            => 'id_sync',
            'aula_huellas_sync'          => 'id',
            'asistencias'                => 'id_asistencia',
            'asistencias_estudiantes'    => 'id_asistencia_estudiante',
            'asistencias_profesores'     => 'id_asistencia_profesor',
            'eventos_asistencia'         => 'id_evento',
        ];

        foreach ($tablas as $tabla => $colPk) {
            $cant = $this->sincronizarTablaInversa($tabla, $colPk);
            $resumen[$tabla] = $cant;
        }

        return [
            'status'     => 'success',
            'direccion'  => 'cloud_to_local',
            'timestamp'  => date('Y-m-d H:i:s'),
            'resumen'    => $resumen,
            'detalles'   => $this->logSync,
        ];
    }

    private function sincronizarTabla(string $tabla, string|array $colPk): int {
        // Verificar si la tabla existe en ambos lados
        if (!$this->tablaExiste($this->pdoLocal, $tabla) || !$this->tablaExiste($this->pdoCloud, $tabla)) {
            return 0;
        }

        // Obtener columnas de la tabla
        $columnas = $this->obtenerColumnas($this->pdoLocal, $tabla);
        if (empty($columnas)) return 0;

        // Leer registros locales
        $stmtLocal = $this->pdoLocal->query("SELECT * FROM public.{$tabla}");
        $filas = $stmtLocal->fetchAll(PDO::FETCH_ASSOC);
        if (empty($filas)) return 0;

        $cantSincronizados = 0;
        $pks = (array)$colPk;

        // Construir sentencia UPSERT en Cloud
        $colsSql = '"' . implode('", "', $columnas) . '"';
        $paramsSql = implode(', ', array_fill(0, count($columnas), '?'));

        $updateParts = [];
        foreach ($columnas as $col) {
            if (in_array($col, $pks, true)) continue;
            $updateParts[] = "\"{$col}\" = EXCLUDED.\"{$col}\"";
        }
        $updateSql = !empty($updateParts) 
            ? "DO UPDATE SET " . implode(', ', $updateParts) 
            : "DO NOTHING";

        $conflictCols = '"' . implode('", "', $pks) . '"';
        $sqlUpsert = "INSERT INTO public.{$tabla} ({$colsSql}) VALUES ({$paramsSql}) ON CONFLICT ({$conflictCols}) {$updateSql}";
        $stmtCloud = $this->pdoCloud->prepare($sqlUpsert);

        foreach ($filas as $fila) {
            // Regla de integridad biométrica: verificar template si es huellas_templates
            if ($tabla === 'huellas_templates') {
                $templateData = $fila['fingerprint_data'] ?? ($fila['huella_template'] ?? null);
                if (!empty($templateData) && $fila['activo'] == 1 && strlen($templateData) !== 3072) {
                    $this->logSync[] = "Advertencia: Huella ID {$fila['id_huella']} omitida por no cumplir contrato de 3072 HEX.";
                    continue;
                }
            }

            $binds = [];
            foreach ($columnas as $col) {
                $binds[] = $fila[$col] ?? null;
            }

            try {
                $stmtCloud->execute($binds);
                $cantSincronizados++;
            } catch (Throwable $e) {
                $pkVal = implode('-', array_map(fn($k) => $fila[$k] ?? '?', $pks));
                $this->logSync[] = "Error sincronizando {$tabla} (PK {$pkVal}): " . $e->getMessage();
            }
        }

        // Actualizar secuencia de PK en la nube
        if (count($pks) === 1) {
            $this->actualizarSecuencia($this->pdoCloud, $tabla, $pks[0]);
        }

        if ($cantSincronizados > 0) {
            $this->logSync[] = "Tabla {$tabla}: {$cantSincronizados} registros sincronizados a la Nube.";
        }

        return $cantSincronizados;
    }

    private function sincronizarTablaInversa(string $tabla, string|array $colPk): int {
        if (!$this->tablaExiste($this->pdoCloud, $tabla) || !$this->tablaExiste($this->pdoLocal, $tabla)) {
            return 0;
        }

        $columnas = $this->obtenerColumnas($this->pdoCloud, $tabla);
        if (empty($columnas)) return 0;

        $stmtCloud = $this->pdoCloud->query("SELECT * FROM public.{$tabla}");
        $filas = $stmtCloud->fetchAll(PDO::FETCH_ASSOC);
        if (empty($filas)) return 0;

        $cant = 0;
        $pks = (array)$colPk;
        $colsSql = '"' . implode('", "', $columnas) . '"';
        $paramsSql = implode(', ', array_fill(0, count($columnas), '?'));

        $updateParts = [];
        foreach ($columnas as $col) {
            if (in_array($col, $pks, true)) continue;
            $updateParts[] = "\"{$col}\" = EXCLUDED.\"{$col}\"";
        }
        $updateSql = !empty($updateParts) 
            ? "DO UPDATE SET " . implode(', ', $updateParts) 
            : "DO NOTHING";

        $conflictCols = '"' . implode('", "', $pks) . '"';
        $sqlUpsert = "INSERT INTO public.{$tabla} ({$colsSql}) VALUES ({$paramsSql}) ON CONFLICT ({$conflictCols}) {$updateSql}";
        $stmtLocal = $this->pdoLocal->prepare($sqlUpsert);

        foreach ($filas as $fila) {
            // Contrato de Integridad Biometrica (1536 bytes / 3072 HEX)
            if ($tabla === 'huellas_templates') {
                $templateData = $fila['fingerprint_data'] ?? ($fila['huella_template'] ?? null);
                if (!empty($templateData) && $fila['activo'] == 1 && strlen($templateData) !== 3072) {
                    $this->logSync[] = "Advertencia: Huella ID {$fila['id_huella']} omitida en descarga por no cumplir contrato de 3072 HEX.";
                    continue;
                }
            }

            $binds = [];
            foreach ($columnas as $col) {
                $binds[] = $fila[$col] ?? null;
            }
            try {
                $stmtLocal->execute($binds);
                $cant++;
            } catch (Throwable $e) {
                $pkVal = implode('-', array_map(fn($k) => $fila[$k] ?? '?', $pks));
                $this->logSync[] = "Error descargando {$tabla} (PK {$pkVal}): " . $e->getMessage();
            }
        }

        if (count($pks) === 1) {
            $this->actualizarSecuencia($this->pdoLocal, $tabla, $pks[0]);
        }
        return $cant;
    }

    private function tablaExiste(PDO $pdo, string $tabla): bool {
        $stmt = $pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
        $stmt->execute([$tabla]);
        return (bool)$stmt->fetch();
    }

    private function obtenerColumnas(PDO $pdo, string $tabla): array {
        $stmt = $pdo->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_schema = 'public' AND table_name = ?
            ORDER BY ordinal_position
        ");
        $stmt->execute([$tabla]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    private function actualizarSecuencia(PDO $pdo, string $tabla, string $colPk): void {
        try {
            $sql = "SELECT setval(pg_get_serial_sequence('public.{$tabla}', '{$colPk}'), coalesce(max(\"{$colPk}\"), 1), true) FROM public.{$tabla}";
            $pdo->query($sql);
        } catch (Throwable $e) {
            // Si la columna no tiene secuencia SERIAL asociada, se ignora silenciosamente
        }
    }
}
