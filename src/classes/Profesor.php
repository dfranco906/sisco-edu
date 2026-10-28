<?php
declare(strict_types=1);

require_once __DIR__ . '/IdentidadHelper.php';

class Profesor {
    private PDO $conn;
    private string $table_name = "profesores";

    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $user_id_global;
    public $id_profesor;
    public $email;
    public $celular;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->apellido = htmlspecialchars(strip_tags((string)$this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags((string)$this->cedula_identidad));
        $this->user_id_global = !empty($this->user_id_global) ? $this->user_id_global : ('PROF_' . uniqid() . '_' . random_int(100, 999));

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Profesor',
            $this->cedula_identidad,
            $this->nombre,
            $this->apellido
        );

        if ($idUsuario && (!empty($this->email) || !empty($this->celular))) {
            $stmtU = $this->conn->prepare("UPDATE usuarios SET email = COALESCE(NULLIF(:email, ''), email), celular = COALESCE(NULLIF(:celular, ''), celular) WHERE id_usuario = :id");
            $stmtU->execute([
                ':email' => !empty($this->email) ? trim((string)$this->email) : null,
                ':celular' => !empty($this->celular) ? trim((string)$this->celular) : null,
                ':id' => $idUsuario
            ]);
        }

        $query = "INSERT INTO " . $this->table_name . " (nombre, apellido, cedula_identidad, user_id_global, id_usuario, activo)
                  VALUES (:nombre, :apellido, :cedula, :user_id_global, :id_usuario, 1)
                  RETURNING id_profesor";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_usuario", $idUsuario);

        if ($stmt->execute()) {
            $this->id_profesor = (int)$stmt->fetchColumn();
            return true;
        }
        return false;
    }

    public function leer(?int $id_grado = null, ?int $id_turno = null, ?int $id_nivel = null, ?int $id_bachillerato = null): PDOStatement {
        $params = [];
        $whereFilters = [];

        if ($id_grado !== null && $id_grado > 0) {
            $whereFilters[] = "ad2.id_grado = :id_grado";
            $params[':id_grado'] = $id_grado;
        }
        if ($id_turno !== null && $id_turno > 0) {
            $whereFilters[] = "g2.id_turno = :id_turno";
            $params[':id_turno'] = $id_turno;
        }
        if ($id_nivel !== null && $id_nivel > 0) {
            $whereFilters[] = "g2.id_nivel = :id_nivel";
            $params[':id_nivel'] = $id_nivel;
        }
        if ($id_bachillerato !== null && $id_bachillerato > 0) {
            $whereFilters[] = "g2.id_bachillerato = :id_bachillerato";
            $params[':id_bachillerato'] = $id_bachillerato;
        }

        $filterSql = "";
        if (!empty($whereFilters)) {
            $filterSql = " AND EXISTS (
                SELECT 1 
                FROM asignacion_docente ad2
                JOIN grados g2 ON ad2.id_grado = g2.id_grado
                WHERE ad2.id_profesor = p.id_profesor
                  AND ad2.activo = 1
                  AND " . implode(" AND ", $whereFilters) . "
            )";
        }

        $query = "SELECT 
                    p.id_profesor,
                    p.nombre,
                    p.apellido,
                    p.cedula_identidad,
                    p.user_id_global,
                    p.id_usuario,
                    p.activo,
                    u.usuario,
                    u.email,
                    u.celular,
                    CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo,
                    COUNT(DISTINCT ad.id_asignacion) AS total_asignaciones,
                    COUNT(DISTINCT ad.id_grado) AS total_grados,
                    STRING_AGG(DISTINCT CASE WHEN m.nombre IS NOT NULL THEN CONCAT(m.nombre, ' (', COALESCE(g.nombre, 'Sin curso'), ')') ELSE NULL END, ', ') AS asignaciones_resumen,
                    STRING_AGG(DISTINCT g.nombre, ', ') AS grados_resumen,
                    STRING_AGG(DISTINCT m.nombre, ', ') AS materias_resumen,
                    STRING_AGG(DISTINCT CAST(g.id_turno AS TEXT), ',') AS turnos_ids,
                    STRING_AGG(DISTINCT CAST(g.id_nivel AS TEXT), ',') AS niveles_ids,
                    STRING_AGG(DISTINCT CAST(g.id_bachillerato AS TEXT), ',') AS bachilleratos_ids,
                    COUNT(DISTINCT ht.id_huella) AS huella_registrada,
                    MAX(ht.id_huella) AS huella_id
                  FROM profesores p
                  LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
                  LEFT JOIN asignacion_docente ad ON ad.id_profesor = p.id_profesor AND ad.activo = 1
                  LEFT JOIN grados g ON ad.id_grado = g.id_grado
                  LEFT JOIN materias m ON ad.id_materia = m.id_materia
                  LEFT JOIN huellas_templates ht ON (ht.id_profesor = p.id_profesor OR (p.user_id_global IS NOT NULL AND ht.user_id_global = p.user_id_global)) AND ht.activo = 1
                  WHERE p.activo = 1 {$filterSql}
                  GROUP BY p.id_profesor, p.nombre, p.apellido, p.cedula_identidad, p.user_id_global, p.id_usuario, p.activo, u.usuario, u.email, u.celular
                  ORDER BY p.apellido ASC, p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function leerDesactivados(): PDOStatement {
        $query = "SELECT 
                    p.id_profesor,
                    p.nombre,
                    p.apellido,
                    p.cedula_identidad,
                    p.user_id_global,
                    p.id_usuario,
                    p.activo,
                    u.usuario,
                    u.email,
                    u.celular,
                    CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                  FROM profesores p
                  LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
                  WHERE p.activo = 0
                  ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): bool {
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->apellido = htmlspecialchars(strip_tags((string)$this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags((string)$this->cedula_identidad));
        $this->user_id_global = !empty($this->user_id_global) ? $this->user_id_global : NULL;
        $this->id_profesor = (int)$this->id_profesor;

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Profesor',
            $this->cedula_identidad,
            $this->nombre,
            $this->apellido
        );

        if ($idUsuario && (!empty($this->email) || !empty($this->celular))) {
            $stmtU = $this->conn->prepare("UPDATE usuarios SET email = COALESCE(NULLIF(:email, ''), email), celular = COALESCE(NULLIF(:celular, ''), celular) WHERE id_usuario = :id");
            $stmtU->execute([
                ':email' => !empty($this->email) ? trim((string)$this->email) : null,
                ':celular' => !empty($this->celular) ? trim((string)$this->celular) : null,
                ':id' => $idUsuario
            ]);
        }

        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre,
                      apellido = :apellido,
                      cedula_identidad = :cedula,
                      user_id_global = COALESCE(:user_id_global, user_id_global),
                      id_usuario = COALESCE(id_usuario, :id_usuario)
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_usuario", $idUsuario);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function desactivar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function restaurar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function contarDependencias(): int {
        $query = "SELECT
                    (SELECT COUNT(*) FROM asignacion_docente WHERE id_profesor=:id1) +
                    (SELECT COUNT(*) FROM asistencias_profesores WHERE id_profesor=:id2) +
                    (SELECT COUNT(*) FROM huellas_templates WHERE id_profesor=:id3)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":id1" => $this->id_profesor,
            ":id2" => $this->id_profesor,
            ":id3" => $this->id_profesor
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function eliminar(): bool {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function importarBatch(array $filas, ?int $idGradoDefecto = null, ?int $idMateriaDefecto = null): array {
        $creados = 0;
        $actualizados = 0;
        $asignaciones = 0;
        $errores = [];

        // 1. Obtener catálogo de grados y materias para mapeo inteligente
        $mapaGrados = [];
        try {
            $stmtG = $this->conn->query("SELECT id_grado, nombre FROM grados WHERE activo = 1");
            while ($row = $stmtG->fetch(PDO::FETCH_ASSOC)) {
                $mapaGrados[mb_strtolower(trim((string)$row['nombre']))] = (int)$row['id_grado'];
            }
        } catch (Throwable $e) {
            error_log("Error cargando grados para importación: " . $e->getMessage());
        }

        $mapaMaterias = [];
        try {
            $stmtM = $this->conn->query("SELECT id_materia, nombre FROM materias WHERE activo = 1");
            while ($row = $stmtM->fetch(PDO::FETCH_ASSOC)) {
                $mapaMaterias[mb_strtolower(trim((string)$row['nombre']))] = (int)$row['id_materia'];
            }
        } catch (Throwable $e) {
            error_log("Error cargando materias para importación: " . $e->getMessage());
        }

        // 2. Sentencias preparadas
        $stmtCheck = $this->conn->prepare("SELECT id_profesor, id_usuario, user_id_global FROM profesores WHERE cedula_identidad = :ci LIMIT 1");
        $stmtUpdateProf = $this->conn->prepare("UPDATE profesores SET nombre = :nombre, apellido = :apellido, activo = 1 WHERE id_profesor = :id");
        $stmtUpdateUser = $this->conn->prepare("UPDATE usuarios SET email = COALESCE(NULLIF(:email, ''), email), celular = COALESCE(NULLIF(:celular, ''), celular) WHERE id_usuario = :id");
        $stmtInsertProf = $this->conn->prepare("INSERT INTO profesores (nombre, apellido, cedula_identidad, user_id_global, id_usuario, activo) VALUES (:nombre, :apellido, :cedula, :user_id_global, :id_usuario, 1) RETURNING id_profesor");
        
        $stmtCheckAsig = $this->conn->prepare("SELECT id_asignacion FROM asignacion_docente WHERE id_profesor = :prof AND id_grado = :grado AND id_materia = :materia AND activo = 1 LIMIT 1");
        $stmtInsertAsig = $this->conn->prepare("INSERT INTO asignacion_docente (id_profesor, id_materia, id_grado, carga_horaria, anio_lectivo, activo) VALUES (:prof, :materia, :grado, 2, EXTRACT(YEAR FROM CURRENT_DATE), 1)");

        $profesoresContabilizados = [];

        foreach ($filas as $idx => $fila) {
            $numFila = $idx + 2; // Considerando cabecera en fila 1

            $cedula = trim((string)($fila['cedula_identidad'] ?? $fila['cedula'] ?? $fila['ci'] ?? ''));
            $nombre = trim((string)($fila['nombre'] ?? $fila['nombres'] ?? ''));
            $apellido = trim((string)($fila['apellido'] ?? $fila['apellidos'] ?? ''));
            $email = trim((string)($fila['email'] ?? $fila['correo'] ?? ''));
            $celular = trim((string)($fila['celular'] ?? $fila['telefono'] ?? ''));
            $gradoInput = trim((string)($fila['grado'] ?? $fila['curso'] ?? ''));
            $materiaInput = trim((string)($fila['materia'] ?? $fila['asignatura'] ?? ''));

            if (empty($cedula)) {
                $errores[] = "Fila #{$numFila}: Cédula de identidad ausente o vacía.";
                continue;
            }
            if (empty($nombre) || empty($apellido)) {
                $errores[] = "Fila #{$numFila} (CI: {$cedula}): Nombre y apellido son requeridos.";
                continue;
            }

            try {
                $stmtCheck->execute([':ci' => $cedula]);
                $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                $idProfesor = null;
                $idUsuario = null;

                if ($existente) {
                    $idProfesor = (int)$existente['id_profesor'];
                    $idUsuario = !empty($existente['id_usuario']) ? (int)$existente['id_usuario'] : null;

                    $stmtUpdateProf->execute([
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':id' => $idProfesor
                    ]);

                    if ($idUsuario && (!empty($email) || !empty($celular))) {
                        $stmtUpdateUser->execute([
                            ':email' => !empty($email) ? $email : null,
                            ':celular' => !empty($celular) ? $celular : null,
                            ':id' => $idUsuario
                        ]);
                    }

                    if (!isset($profesoresContabilizados[$cedula])) {
                        $actualizados++;
                        $profesoresContabilizados[$cedula] = true;
                    }
                } else {
                    $idUsuario = IdentidadHelper::asegurarUsuario(
                        $this->conn,
                        'Profesor',
                        $cedula,
                        $nombre,
                        $apellido
                    );

                    if ($idUsuario && (!empty($email) || !empty($celular))) {
                        $stmtUpdateUser->execute([
                            ':email' => !empty($email) ? $email : null,
                            ':celular' => !empty($celular) ? $celular : null,
                            ':id' => $idUsuario
                        ]);
                    }

                    $userIdGlobal = 'PROF_' . preg_replace('/\D/', '', $cedula) . '_' . substr(uniqid(), -4);

                    $stmtInsertProf->execute([
                        ':nombre' => $nombre,
                        ':apellido' => $apellido,
                        ':cedula' => $cedula,
                        ':user_id_global' => $userIdGlobal,
                        ':id_usuario' => $idUsuario
                    ]);
                    $idProfesor = (int)$stmtInsertProf->fetchColumn();

                    if (!isset($profesoresContabilizados[$cedula])) {
                        $creados++;
                        $profesoresContabilizados[$cedula] = true;
                    }
                }

                // Resolver posible lista de grados (permite varios separados por comas o barras)
                $gradosList = [];
                if (!empty($gradoInput)) {
                    $tokensG = preg_split('/[,;\/|]+/', $gradoInput);
                    foreach ($tokensG as $tokG) {
                        $tokTrimG = trim((string)$tokG);
                        if (empty($tokTrimG)) continue;
                        $keyG = mb_strtolower($tokTrimG);
                        if (isset($mapaGrados[$keyG])) {
                            $gradosList[] = $mapaGrados[$keyG];
                        } else {
                            foreach ($mapaGrados as $nomG => $idG) {
                                if (str_contains($nomG, $keyG) || str_contains($keyG, $nomG)) {
                                    $gradosList[] = $idG;
                                    break;
                                }
                            }
                        }
                    }
                } elseif ($idGradoDefecto) {
                    $gradosList[] = $idGradoDefecto;
                }

                // Resolver posible lista de materias (permite varias separadas por comas o barras)
                $materiasList = [];
                if (!empty($materiaInput)) {
                    $tokensM = preg_split('/[,;\/|]+/', $materiaInput);
                    foreach ($tokensM as $tokM) {
                        $tokTrimM = trim((string)$tokM);
                        if (empty($tokTrimM)) continue;
                        $keyM = mb_strtolower($tokTrimM);
                        if (isset($mapaMaterias[$keyM])) {
                            $materiasList[] = $mapaMaterias[$keyM];
                        } else {
                            foreach ($mapaMaterias as $nomM => $idM) {
                                if (str_contains($nomM, $keyM) || str_contains($keyM, $nomM)) {
                                    $materiasList[] = $idM;
                                    break;
                                }
                            }
                        }
                    }
                } elseif ($idMateriaDefecto) {
                    $materiasList[] = $idMateriaDefecto;
                }

                // Vincular asignaciones docentes evitando duplicados
                if ($idProfesor && !empty($gradosList) && !empty($materiasList)) {
                    $gradosListUniq = array_unique($gradosList);
                    $materiasListUniq = array_unique($materiasList);

                    foreach ($gradosListUniq as $idG) {
                        foreach ($materiasListUniq as $idM) {
                            $stmtCheckAsig->execute([
                                ':prof' => $idProfesor,
                                ':grado' => $idG,
                                ':materia' => $idM
                            ]);
                            if (!$stmtCheckAsig->fetchColumn()) {
                                $stmtInsertAsig->execute([
                                    ':prof' => $idProfesor,
                                    ':materia' => $idM,
                                    ':grado' => $idG
                                ]);
                                $asignaciones++;
                            }
                        }
                    }
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
            'asignaciones' => $asignaciones,
            'errores' => $errores
        ];
    }
}
