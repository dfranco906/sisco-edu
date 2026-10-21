<?php
declare(strict_types=1);

require_once __DIR__ . '/IdentidadHelper.php';

class Estudiante {
    private PDO $conn;

    public $id_estudiante;
    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $id_grado;
    public $user_id_global;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function matricularEnMateriasDeGrado(int $id_estudiante, int $id_grado): int {
        if ($id_estudiante <= 0 || $id_grado <= 0) {
            return 0;
        }

        $stmt = $this->conn->prepare("
            SELECT DISTINCT ad.id_materia, ad.id_asignacion
            FROM asignacion_docente ad
            WHERE ad.id_grado = :id_grado AND ad.activo = 1
        ");
        $stmt->execute([':id_grado' => $id_grado]);
        $materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($materias)) {
            return 0;
        }

        $stmtMatricula = $this->conn->prepare("
            INSERT INTO matriculas (id_estudiante, id_grado, id_materia, id_asignacion, anho_lectivo, activo)
            VALUES (:id_estudiante, :id_grado, :id_materia, :id_asignacion, 2026, 1)
            ON CONFLICT (id_estudiante, id_materia, anho_lectivo)
            DO UPDATE SET id_grado = EXCLUDED.id_grado, id_asignacion = EXCLUDED.id_asignacion, activo = 1
        ");

        $total = 0;
        foreach ($materias as $m) {
            $ok = $stmtMatricula->execute([
                ':id_estudiante' => $id_estudiante,
                ':id_grado' => $id_grado,
                ':id_materia' => (int)$m['id_materia'],
                ':id_asignacion' => !empty($m['id_asignacion']) ? (int)$m['id_asignacion'] : null
            ]);
            if ($ok) $total++;
        }

        return $total;
    }

    public function sincronizarTodasLasMatriculas(?int $idGrado = null): array {
        $sql = "SELECT id_estudiante, id_grado FROM estudiantes WHERE activo = 1 AND id_grado IS NOT NULL";
        $params = [];
        if ($idGrado !== null && $idGrado > 0) {
            $sql .= " AND id_grado = :id_grado";
            $params[':id_grado'] = $idGrado;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalEstudiantes = 0;
        $totalMatriculas = 0;

        foreach ($estudiantes as $e) {
            $mats = $this->matricularEnMateriasDeGrado((int)$e['id_estudiante'], (int)$e['id_grado']);
            $totalMatriculas += $mats;
            $totalEstudiantes++;
        }

        return [
            'estudiantes_procesados' => $totalEstudiantes,
            'matriculas_sincronizadas' => $totalMatriculas
        ];
    }

    public function crear(): array {
        $nombre = trim((string)$this->nombre);
        $apellido = trim((string)$this->apellido);
        $cedula = trim((string)$this->cedula_identidad);

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Estudiante',
            $cedula,
            $nombre,
            $apellido
        );

        $query = "INSERT INTO estudiantes (nombre, apellido, cedula_identidad, id_grado, user_id_global, id_usuario, activo)
                  VALUES (:nombre, :apellido, :cedula, :id_grado, :user_id_global, :id_usuario, 1)
                  RETURNING id_estudiante";

        $stmt = $this->conn->prepare($query);

        $ok = $stmt->execute([
            ":nombre" => $nombre,
            ":apellido" => $apellido,
            ":cedula" => $cedula,
            ":id_grado" => !empty($this->id_grado) ? (int)$this->id_grado : null,
            ":user_id_global" => !empty($this->user_id_global) ? $this->user_id_global : ('EST_' . uniqid() . '_' . random_int(100, 999)),
            ":id_usuario" => $idUsuario
        ]);

        $matriculadas = 0;
        if ($ok) {
            $idNuevo = (int)$stmt->fetchColumn();
            $this->id_estudiante = $idNuevo;
            if (!empty($this->id_grado)) {
                $matriculadas = $this->matricularEnMateriasDeGrado($idNuevo, (int)$this->id_grado);
            }
        }

        return [
            'ok' => $ok,
            'id_estudiante' => $this->id_estudiante,
            'materias_matriculadas' => $matriculadas
        ];
    }

    public function leer(?int $id_grado = null, ?int $id_turno = null, ?int $id_nivel = null, ?int $id_bachillerato = null): PDOStatement {
        $params = [];
        $where = "WHERE e.activo = 1";

        if ($id_grado !== null && $id_grado > 0) {
            $where .= " AND e.id_grado = :id_grado";
            $params[':id_grado'] = $id_grado;
        }
        if ($id_turno !== null && $id_turno > 0) {
            $where .= " AND g.id_turno = :id_turno";
            $params[':id_turno'] = $id_turno;
        }
        if ($id_nivel !== null && $id_nivel > 0) {
            $where .= " AND g.id_nivel = :id_nivel";
            $params[':id_nivel'] = $id_nivel;
        }
        if ($id_bachillerato !== null && $id_bachillerato > 0) {
            $where .= " AND g.id_bachillerato = :id_bachillerato";
            $params[':id_bachillerato'] = $id_bachillerato;
        }

        $query = "SELECT 
                    e.id_estudiante,
                    e.nombre,
                    e.apellido,
                    e.cedula_identidad,
                    e.user_id_global,
                    e.id_grado,
                    g.nombre AS grado,
                    g.id_turno,
                    g.id_nivel,
                    g.id_bachillerato,
                    COALESCE(m.total_materias, 0) AS materias_matriculadas,
                    COALESCE(e.huella_registrada, 0) AS huella_registrada,
                    e.huella_id,
                    COUNT(DISTINCT et.id_tutor) AS total_tutores,
                    STRING_AGG(DISTINCT CASE WHEN t.id_tutor IS NOT NULL THEN CONCAT(t.nombre, ' ', t.apellido, ' (', COALESCE(et.parentesco, 'Tutor'), ')') ELSE NULL END, ', ') AS tutores_resumen,
                    e.activo,
                    CONCAT(e.nombre, ' ', e.apellido, ' - CI: ', e.cedula_identidad) AS nombre_completo
                  FROM estudiantes e
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  LEFT JOIN (
                      SELECT id_estudiante, COUNT(*) AS total_materias
                      FROM matriculas
                      WHERE activo = 1
                      GROUP BY id_estudiante
                  ) m ON e.id_estudiante = m.id_estudiante
                  LEFT JOIN estudiante_tutores et ON et.id_estudiante = e.id_estudiante AND et.activo = 1
                  LEFT JOIN tutores t ON et.id_tutor = t.id_tutor AND t.activo = 1
                  {$where}
                  GROUP BY e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.user_id_global, e.id_grado, g.nombre, g.id_turno, g.id_nivel, g.id_bachillerato, m.total_materias, e.huella_registrada, e.huella_id, e.activo
                  ORDER BY g.nombre ASC, e.apellido ASC, e.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function leerDesactivados(): PDOStatement {
        $query = "SELECT 
                    e.id_estudiante,
                    e.nombre,
                    e.apellido,
                    e.cedula_identidad,
                    e.user_id_global,
                    e.id_grado,
                    g.nombre AS grado,
                    COALESCE(m.total_materias, 0) AS materias_matriculadas,
                    e.activo,
                    CONCAT(e.nombre, ' ', e.apellido, ' - CI: ', e.cedula_identidad) AS nombre_completo
                  FROM estudiantes e
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  LEFT JOIN (
                      SELECT id_estudiante, COUNT(*) AS total_materias
                      FROM matriculas
                      WHERE activo = 1
                      GROUP BY id_estudiante
                  ) m ON e.id_estudiante = m.id_estudiante
                  WHERE e.activo = 0
                  ORDER BY e.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): bool {
        if (empty($this->id_estudiante)) return false;

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Estudiante',
            (string)$this->cedula_identidad,
            (string)$this->nombre,
            (string)$this->apellido
        );

        $campos = [
            "nombre=:nombre",
            "apellido=:apellido",
            "cedula_identidad=:cedula",
            "id_usuario = COALESCE(id_usuario, :id_usuario)"
        ];

        $params = [
            ":nombre" => $this->nombre,
            ":apellido" => $this->apellido,
            ":cedula" => $this->cedula_identidad,
            ":id_usuario" => $idUsuario,
            ":id" => $this->id_estudiante
        ];

        if (!empty($this->user_id_global)) {
            $campos[] = "user_id_global=:user_id_global";
            $params[":user_id_global"] = $this->user_id_global;
        }

        if (!empty($this->id_grado)) {
            $campos[] = "id_grado=:id_grado";
            $params[":id_grado"] = $this->id_grado;
        }

        $query = "UPDATE estudiantes
                  SET " . implode(", ", $campos) . "
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        $ok = $stmt->execute($params);

        if ($ok && !empty($this->id_grado)) {
            $this->matricularEnMateriasDeGrado((int)$this->id_estudiante, (int)$this->id_grado);
        }

        return $ok;
    }

    public function desactivar(): bool {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=0
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function restaurar(): bool {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=1
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function eliminar(): bool {
        if (empty($this->id_estudiante)) return false;

        $query = "DELETE FROM estudiantes WHERE id_estudiante=:id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    /**
     * Búsqueda predictiva de estudiantes con datos de curso y resumen de tutores vinculados.
     */
    public function buscar(string $q = '', ?int $id_grado = null, int $limit = 20): array {
        $limit = max(1, min(50, $limit));
        $params = [];
        $where = "WHERE e.activo = 1";

        $q = trim($q);
        if ($q !== '') {
            $where .= " AND (e.nombre ILIKE :q OR e.apellido ILIKE :q OR e.cedula_identidad ILIKE :q)";
            $params[':q'] = "%{$q}%";
        }

        if ($id_grado !== null && $id_grado > 0) {
            $where .= " AND e.id_grado = :id_grado";
            $params[':id_grado'] = $id_grado;
        }

        $sql = "SELECT e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.id_grado,
                       g.nombre AS grado_nombre, g.seccion, g.turno,
                       COUNT(DISTINCT et.id_tutor) AS total_tutores,
                       STRING_AGG(DISTINCT CASE WHEN t.id_tutor IS NOT NULL THEN CONCAT(t.nombre, ' ', t.apellido, ' (', COALESCE(et.parentesco, 'Tutor'), ')') ELSE NULL END, ', ') AS tutores_resumen
                FROM estudiantes e
                LEFT JOIN grados g ON e.id_grado = g.id_grado
                LEFT JOIN estudiante_tutores et ON et.id_estudiante = e.id_estudiante AND et.activo = 1
                LEFT JOIN tutores t ON et.id_tutor = t.id_tutor AND t.activo = 1
                $where
                GROUP BY e.id_estudiante, e.nombre, e.apellido, e.cedula_identidad, e.id_grado, g.nombre, g.seccion, g.turno
                ORDER BY g.nombre ASC, e.apellido ASC, e.nombre ASC
                LIMIT {$limit}";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Importación masiva por lotes de estudiantes desde archivo CSV / tabular.
     * Es idempotente: si la cédula ya existe, actualiza los datos y rematricula si cambia de grado.
     */
    public function importarBatch(array $filas, ?int $idGradoDefecto = null): array {
        if (empty($filas)) {
            return [
                'status' => false,
                'total_filas' => 0,
                'creados' => 0,
                'actualizados' => 0,
                'errores' => ['El archivo no contiene filas de datos para procesar.']
            ];
        }

        require_once __DIR__ . '/IdentidadHelper.php';

        // Mapeo en memoria de nombres de grados para resolución rápida
        $stmtGrados = $this->conn->query("SELECT id_grado, nombre FROM grados WHERE activo = 1");
        $mapaGrados = [];
        while ($g = $stmtGrados->fetch(PDO::FETCH_ASSOC)) {
            $mapaGrados[mb_strtolower(trim((string)$g['nombre']))] = (int)$g['id_grado'];
        }

        $stmtCheck = $this->conn->prepare("
            SELECT id_estudiante, id_grado, user_id_global
            FROM estudiantes
            WHERE cedula_identidad = :ci AND activo = 1
            LIMIT 1
        ");

        $stmtUpdate = $this->conn->prepare("
            UPDATE estudiantes
            SET nombre = :nombre,
                apellido = :apellido,
                id_grado = COALESCE(:id_grado, id_grado)
            WHERE id_estudiante = :id
        ");

        $stmtInsert = $this->conn->prepare("
            INSERT INTO estudiantes (nombre, apellido, cedula_identidad, id_grado, user_id_global, id_usuario, activo)
            VALUES (:nombre, :apellido, :cedula, :id_grado, :user_id_global, :id_usuario, 1)
            RETURNING id_estudiante
        ");

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        foreach ($filas as $idx => $fila) {
            $numFila = $idx + 2; // Considerando cabecera en fila 1

            $cedula = trim((string)($fila['cedula_identidad'] ?? $fila['cedula'] ?? $fila['ci'] ?? ''));
            $nombre = trim((string)($fila['nombre'] ?? $fila['nombres'] ?? ''));
            $apellido = trim((string)($fila['apellido'] ?? $fila['apellidos'] ?? ''));
            $gradoInput = trim((string)($fila['grado'] ?? $fila['curso'] ?? ''));

            if (empty($cedula)) {
                $errores[] = "Fila #{$numFila}: Cédula de identidad ausente o vacía.";
                continue;
            }
            if (empty($nombre) || empty($apellido)) {
                $errores[] = "Fila #{$numFila} (CI: {$cedula}): Nombre y apellido son requeridos.";
                continue;
            }

            // Resolver grado
            $idGrado = $idGradoDefecto;
            if (!empty($gradoInput)) {
                $key = mb_strtolower($gradoInput);
                if (isset($mapaGrados[$key])) {
                    $idGrado = $mapaGrados[$key];
                } else {
                    // Búsqueda parcial si no coincide exactamente
                    foreach ($mapaGrados as $gNombre => $gId) {
                        if (str_contains($gNombre, $key) || str_contains($key, $gNombre)) {
                            $idGrado = $gId;
                            break;
                        }
                    }
                }
            }

            try {
                $stmtCheck->execute([':ci' => $cedula]);
                $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existente) {
                    $idEst = (int)$existente['id_estudiante'];
                    $stmtUpdate->execute([
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':id_grado' => $idGrado,
                        ':id' => $idEst
                    ]);

                    if ($idGrado && $idGrado !== (int)$existente['id_grado']) {
                        $this->matricularEnMateriasDeGrado($idEst, $idGrado);
                    }
                    $actualizados++;
                } else {
                    $idUsuario = IdentidadHelper::asegurarUsuario(
                        $this->conn,
                        'Estudiante',
                        $cedula,
                        $nombre,
                        $apellido
                    );

                    $userIdGlobal = 'EST_' . preg_replace('/\D/', '', $cedula) . '_' . substr(uniqid(), -4);

                    $stmtInsert->execute([
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':cedula' => $cedula,
                        ':id_grado' => $idGrado,
                        ':user_id_global' => $userIdGlobal,
                        ':id_usuario' => $idUsuario
                    ]);
                    $idNuevo = (int)$stmtInsert->fetchColumn();

                    if ($idNuevo && $idGrado) {
                        $this->matricularEnMateriasDeGrado($idNuevo, $idGrado);
                    }
                    $creados++;
                }
            } catch (Throwable $e) {
                error_log("Error importando fila #{$numFila} (CI: {$cedula}): " . $e->getMessage());
                $errores[] = "Fila #{$numFila} (CI: {$cedula}): Error al registrar en base de datos.";
            }
        }

        return [
            'status' => count($errores) === 0 || ($creados + $actualizados > 0),
            'total_filas' => count($filas),
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => $errores
        ];
    }
}
