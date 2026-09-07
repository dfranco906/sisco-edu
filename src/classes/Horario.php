<?php

class Horario
{
    private $conn;
    private $table_name = "horarios";

    public $id_horario;
    public $id_asignacion;
    public $id_grado;
    public $dia_semana;
    public $hora_inicio;
    public $hora_fin;
    public $id_aula;
    public $permite_superposicion = 0;
    public $id_horario_vinculado;
    public $id_grupo_clase_conjunta;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerAsignacionActiva($id_asignacion)
    {
        $stmt = $this->conn->prepare("
            SELECT
                ad.id_asignacion,
                ad.id_grado,
                ad.id_profesor,
                ad.id_materia,
                ad.anio_lectivo,
                g.nombre AS grado,
                g.id_aula,
                m.nombre AS materia
            FROM asignacion_docente ad
            INNER JOIN profesores p ON p.id_profesor = ad.id_profesor AND p.activo = 1
            INNER JOIN materias m ON m.id_materia = ad.id_materia AND m.activo = 1
            INNER JOIN grados g ON g.id_grado = ad.id_grado AND g.activo = 1
            INNER JOIN aulas a ON a.id_aula = g.id_aula AND a.activo = 1
            WHERE ad.id_asignacion = :id_asignacion
              AND ad.activo = 1
            LIMIT 1
        ");
        $stmt->execute([":id_asignacion" => $id_asignacion]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function interfiereRecesoTercerCiclo(array $asignacion, $horaInicio, $horaFin)
    {
        $nombreMateria = strtoupper(trim((string) ($asignacion['materia'] ?? '')));
        if (in_array($nombreMateria, ['RECESO', 'RECREO'], true)) return false;

        $nombreGrado = (string) ($asignacion['grado'] ?? '');
        if (!preg_match('/(^|\D)(7|8|9)(\D|$)/u', $nombreGrado)) return false;

        $inicio = strtotime((string) $horaInicio);
        $fin = strtotime((string) $horaFin);
        $inicioReceso = strtotime('09:40');
        $finReceso = strtotime('10:10');

        return $inicio < $finReceso && $fin > $inicioReceso;
    }

    public function obtenerHorarioConjuntoActivo($id_horario)
    {
        $stmt = $this->conn->prepare("
            SELECT
                h.id_horario,
                h.id_asignacion,
                h.id_grado,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                h.id_aula,
                h.id_horario_vinculado,
                h.id_grupo_clase_conjunta,
                ad.id_profesor,
                ad.id_materia,
                ad.anio_lectivo
            FROM horarios h
            INNER JOIN asignacion_docente ad
                ON ad.id_asignacion = h.id_asignacion
               AND ad.activo = 1
            WHERE h.id_horario = :id_horario
              AND h.activo = 1
            LIMIT 1
        ");
        $stmt->execute([":id_horario" => $id_horario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function obtenerHorarioExactoAsignacion($idAsignacion, $diaSemana, $horaInicio, $horaFin, $excluirId = null)
    {
        $query = "
            SELECT h.id_horario, h.id_asignacion, h.id_grado, h.id_aula,
                   h.dia_semana, h.hora_inicio, h.hora_fin,
                   h.permite_superposicion, h.id_horario_vinculado
                   , h.id_grupo_clase_conjunta
            FROM horarios h
            WHERE h.id_asignacion = :id_asignacion
              AND h.dia_semana = :dia_semana
              AND h.hora_inicio = :hora_inicio
              AND h.hora_fin = :hora_fin
              AND h.activo = 1
        ";
        $params = [
            ':id_asignacion' => $idAsignacion,
            ':dia_semana' => $diaSemana,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ];
        if ($excluirId) {
            $query .= " AND h.id_horario <> :excluir_id";
            $params[':excluir_id'] = $excluirId;
        }
        $query .= " LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function vincularHorarios($idHorarioA, $idHorarioB)
    {
        return $this->vincularGrupoHorarios([$idHorarioA, $idHorarioB]);
    }

    public function vincularGrupoHorarios(array $idsHorarios)
    {
        $idsHorarios = array_values(array_unique(array_filter(array_map('intval', $idsHorarios))));
        if (count($idsHorarios) < 2) return false;
        sort($idsHorarios, SORT_NUMERIC);
        $idGrupo = $idsHorarios[0];

        $marcadoresIds = implode(',', array_fill(0, count($idsHorarios), '?'));
        $buscarGrupos = $this->conn->prepare("
            SELECT DISTINCT id_grupo_clase_conjunta
            FROM horarios
            WHERE id_horario IN ({$marcadoresIds})
              AND id_grupo_clase_conjunta IS NOT NULL
        ");
        $buscarGrupos->execute($idsHorarios);
        $gruposAnteriores = array_map('intval', $buscarGrupos->fetchAll(PDO::FETCH_COLUMN));
        $gruposAnteriores[] = $idGrupo;
        if ($this->id_grupo_clase_conjunta) $gruposAnteriores[] = (int) $this->id_grupo_clase_conjunta;
        $gruposAnteriores = array_values(array_unique($gruposAnteriores));

        $marcadoresGrupos = implode(',', array_fill(0, count($gruposAnteriores), '?'));
        $limpiar = $this->conn->prepare("
            UPDATE horarios
            SET permite_superposicion = 0,
                id_horario_vinculado = NULL,
                id_grupo_clase_conjunta = NULL
            WHERE id_grupo_clase_conjunta IN ({$marcadoresGrupos})
              AND id_horario NOT IN ({$marcadoresIds})
        ");
        $limpiar->execute(array_merge($gruposAnteriores, $idsHorarios));

        $stmt = $this->conn->prepare("
            UPDATE horarios
            SET permite_superposicion = 1,
                id_horario_vinculado = :id_vinculado,
                id_grupo_clase_conjunta = :id_grupo
            WHERE id_horario = :id_horario
              AND activo = 1
        ");
        foreach ($idsHorarios as $indice => $idHorario) {
            $idVinculado = $indice === 0 ? $idsHorarios[1] : $idsHorarios[0];
            if (!$stmt->execute([
                ':id_vinculado' => $idVinculado,
                ':id_grupo' => $idGrupo,
                ':id_horario' => $idHorario
            ])) return false;
        }
        return true;
    }

    public function obtenerHorariosGrupo($idHorario)
    {
        $actual = $this->obtenerHorarioConjuntoActivo($idHorario);
        if (!$actual) return [];
        $idGrupo = $actual['id_grupo_clase_conjunta'] ?? null;
        if (!$idGrupo) return [$actual];

        $stmt = $this->conn->prepare("
            SELECT h.id_horario, h.id_asignacion, h.id_grado, h.id_aula,
                   h.dia_semana, h.hora_inicio, h.hora_fin,
                   h.permite_superposicion, h.id_horario_vinculado,
                   h.id_grupo_clase_conjunta
            FROM horarios h
            WHERE h.id_grupo_clase_conjunta = :id_grupo
              AND h.activo = 1
            ORDER BY h.id_horario
        ");
        $stmt->execute([':id_grupo' => $idGrupo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function desvincularHorario($idHorario)
    {
        $actual = $this->obtenerHorarioConjuntoActivo($idHorario);
        $idVinculado = $actual['id_horario_vinculado'] ?? null;
        $idGrupo = $actual['id_grupo_clase_conjunta'] ?? null;

        $stmt = $this->conn->prepare("
            UPDATE horarios
            SET permite_superposicion = 0,
                id_horario_vinculado = NULL,
                id_grupo_clase_conjunta = NULL
            WHERE id_horario IN (:id_horario, :id_vinculado)
               OR id_horario_vinculado = :id_horario_relacionado
               OR (:id_grupo_presente = 1 AND id_grupo_clase_conjunta = :id_grupo)
        ");
        return $stmt->execute([
            ':id_horario' => $idHorario,
            ':id_vinculado' => $idVinculado ?: $idHorario,
            ':id_horario_relacionado' => $idHorario,
            ':id_grupo_presente' => $idGrupo ? 1 : 0,
            ':id_grupo' => $idGrupo ?: 0
        ]);
    }

    public function validarGruposSeleccionados(array $horariosSeleccionados, array $idsAsignaciones, $grupoEditable = null): void
    {
        $idsAsignaciones = array_map('intval', $idsAsignaciones);
        foreach ($horariosSeleccionados as $seleccionado) {
            $grupo = $seleccionado['id_grupo_clase_conjunta'] ?? null;
            if (!$grupo || ($grupoEditable && (int)$grupo === (int)$grupoEditable)) continue;
            foreach ($this->obtenerHorariosGrupo($seleccionado['id_horario']) as $miembro) {
                if (!in_array((int)$miembro['id_asignacion'], $idsAsignaciones, true)) {
                    throw new DomainException('Una clase seleccionada ya pertenece a otro grupo conjunto. Incluya todos sus cursos o edite primero ese grupo.');
                }
            }
        }
    }

    public function obtenerConflicto($excluirId = null, $permitirClaseConjunta = false, $idHorarioVinculado = null)
    {
        $idsVinculados = is_array($idHorarioVinculado) ? $idHorarioVinculado : [$idHorarioVinculado];
        $idsVinculados = array_values(array_unique(array_filter(array_map('intval', $idsVinculados))));
        $paramsVinculados = [];
        $marcadoresVinculados = [];
        foreach ($idsVinculados as $indice => $idVinculado) {
            $marcador = ":id_horario_vinculado_{$indice}";
            $marcadoresVinculados[] = $marcador;
            $paramsVinculados[$marcador] = $idVinculado;
        }
        $condicionVinculada = $marcadoresVinculados
            ? "h.id_horario IN (" . implode(',', $marcadoresVinculados) . ")"
            : "1 = 0";

        $query = "SELECT
                    h.id_horario,
                    h.dia_semana,
                    h.hora_inicio,
                    h.hora_fin,
                    COALESCE(NULLIF(m_existente.nombre, ''), 'Materia sin nombre') AS materia,
                    COALESCE(NULLIF(g_existente.nombre, ''), 'Grado sin nombre') AS grado,
                    COALESCE(NULLIF(a_existente.nombre, ''), 'Aula sin nombre') AS aula,
                    TRIM(CONCAT(COALESCE(p_existente.nombre, ''), ' ', COALESCE(p_existente.apellido, ''))) AS profesor,
                    CASE
                        WHEN h.id_grado = :id_grado_tipo THEN 'grado'
                        WHEN h.id_aula = :id_aula_tipo THEN 'aula'
                        ELSE 'profesor'
                    END AS tipo,
                    CASE
                        WHEN h.id_grado IS NOT NULL
                         AND h.id_grado <> :id_grado_excepcion_info
                         AND ad_existente.id_profesor = ad_nueva.id_profesor
                         AND ad_existente.anio_lectivo = ad_nueva.anio_lectivo
                        THEN 1 ELSE 0
                    END AS excepcion_disponible
                  FROM horarios h
                  INNER JOIN asignacion_docente ad_existente
                    ON ad_existente.id_asignacion = h.id_asignacion
                  INNER JOIN asignacion_docente ad_nueva
                    ON ad_nueva.id_asignacion = :id_asignacion_nueva
                  LEFT JOIN materias m_existente
                    ON m_existente.id_materia = ad_existente.id_materia
                  LEFT JOIN grados g_existente
                    ON g_existente.id_grado = h.id_grado
                  LEFT JOIN aulas a_existente
                    ON a_existente.id_aula = h.id_aula
                  LEFT JOIN profesores p_existente
                    ON p_existente.id_profesor = ad_existente.id_profesor
                  WHERE h.activo = 1
                    AND h.dia_semana = :dia_semana
                    AND h.hora_inicio < :hora_fin
                    AND h.hora_fin > :hora_inicio
                    AND (
                        h.id_grado = :id_grado_conflicto
                        OR h.id_aula = :id_aula_conflicto
                        OR ad_existente.id_profesor = ad_nueva.id_profesor
                    )
                    AND NOT (
                        :permitir_superposicion = 1
                        AND h.id_grado IS NOT NULL
                        AND h.id_grado <> :id_grado_excepcion
                        AND ad_existente.id_profesor = ad_nueva.id_profesor
                        AND ad_existente.anio_lectivo = ad_nueva.anio_lectivo
                        AND ad_existente.activo = 1
                        AND (" . $condicionVinculada . ")
                    )";

        $params = [
            ":id_grado_tipo" => $this->id_grado,
            ":id_aula_tipo" => $this->id_aula,
            ":id_grado_excepcion_info" => $this->id_grado,
            ":id_asignacion_nueva" => $this->id_asignacion,
            ":dia_semana" => $this->dia_semana,
            ":hora_fin" => $this->hora_fin,
            ":hora_inicio" => $this->hora_inicio,
            ":id_grado_conflicto" => $this->id_grado,
            ":id_aula_conflicto" => $this->id_aula,
            ":permitir_superposicion" => $permitirClaseConjunta ? 1 : 0,
            ":id_grado_excepcion" => $this->id_grado
        ];
        $params += $paramsVinculados;

        if ($excluirId !== null) {
            $query .= " AND h.id_horario <> :excluir_id";
            $params[":excluir_id"] = $excluirId;
        }

        $query .= " ORDER BY
                    CASE
                        WHEN h.id_grado = :id_grado_orden THEN 0
                        WHEN h.id_aula = :id_aula_orden THEN 1
                        ELSE 2
                    END,
                    h.hora_inicio
                    LIMIT 1";
        $params[":id_grado_orden"] = $this->id_grado;
        $params[":id_aula_orden"] = $this->id_aula;
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function describirConflicto(array $conflicto)
    {
        $dia = $conflicto['dia_semana'] ?? $this->dia_semana;
        $horaInicio = substr((string) ($conflicto['hora_inicio'] ?? $this->hora_inicio), 0, 5);
        $horaFin = substr((string) ($conflicto['hora_fin'] ?? $this->hora_fin), 0, 5);
        $materia = $conflicto['materia'] ?? 'otra materia';
        $grado = $conflicto['grado'] ?? 'otro grado';
        $aula = $conflicto['aula'] ?? 'otra aula';
        $profesor = trim((string) ($conflicto['profesor'] ?? ''));

        switch ($conflicto['tipo'] ?? '') {
            case 'grado':
                return "El grado {$grado} ya tiene {$materia} el {$dia} de {$horaInicio} a {$horaFin}.";
            case 'aula':
                return "El aula {$aula} ya está ocupada por {$materia} ({$grado}) el {$dia} de {$horaInicio} a {$horaFin}.";
            case 'profesor':
                $sujeto = $profesor !== '' ? "El profesor {$profesor}" : 'El profesor seleccionado';
                return "{$sujeto} ya dicta {$materia} en {$grado} el {$dia} de {$horaInicio} a {$horaFin}.";
            default:
                return "Existe un horario superpuesto el {$dia} de {$horaInicio} a {$horaFin}.";
        }
    }

    public function crear()
    {
        $stmt = $this->conn->prepare("
            INSERT INTO horarios
                (id_asignacion, id_grado, dia_semana, hora_inicio, hora_fin, id_aula, permite_superposicion)
            VALUES
                (:id_asignacion, :id_grado, :dia_semana, :hora_inicio, :hora_fin, :id_aula, :permite_superposicion)
        ");

        return $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":id_grado" => $this->id_grado,
            ":dia_semana" => $this->dia_semana,
            ":hora_inicio" => $this->hora_inicio,
            ":hora_fin" => $this->hora_fin,
            ":id_aula" => $this->id_aula,
            ":permite_superposicion" => $this->permite_superposicion ? 1 : 0
        ]);
    }

    private function consultaListado($activo)
    {
        $stmt = $this->conn->prepare("
            SELECT
                h.id_horario,
                h.id_asignacion,
                h.id_grado,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                h.id_aula,
                h.permite_superposicion,
                h.id_horario_vinculado,
                h.id_grupo_clase_conjunta,
                h.activo,
                ad.id_profesor,
                ad.id_materia,
                COALESCE(NULLIF(g.nombre, ''), NULLIF(h.grado, ''), 'Sin grado') AS grado,
                COALESCE(NULLIF(a.nombre, ''), NULLIF(h.aula, ''), 'Sin aula') AS aula,
                a.codigo AS codigo_aula,
                m.nombre AS materia,
                CONCAT(p.nombre, ' ', p.apellido) AS profesor
            FROM horarios h
            LEFT JOIN grados g ON h.id_grado = g.id_grado
            LEFT JOIN aulas a ON h.id_aula = a.id_aula
            LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
            LEFT JOIN materias m ON ad.id_materia = m.id_materia
            LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
            WHERE h.activo = :activo
            ORDER BY
                COALESCE(NULLIF(g.nombre, ''), NULLIF(h.grado, '')),
                FIELD(h.dia_semana, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'),
                h.hora_inicio
        ");
        $stmt->execute([":activo" => $activo]);

        return $stmt;
    }

    public function leer()
    {
        return $this->consultaListado(1);
    }

    public function leerDesactivados()
    {
        return $this->consultaListado(0);
    }

    public function actualizar()
    {
        $stmt = $this->conn->prepare("
            UPDATE " . $this->table_name . "
            SET id_asignacion = :id_asignacion,
                id_grado = :id_grado,
                dia_semana = :dia_semana,
                hora_inicio = :hora_inicio,
                hora_fin = :hora_fin,
                id_aula = :id_aula,
                permite_superposicion = :permite_superposicion
            WHERE id_horario = :id_horario
        ");

        return $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":id_grado" => $this->id_grado,
            ":dia_semana" => $this->dia_semana,
            ":hora_inicio" => $this->hora_inicio,
            ":hora_fin" => $this->hora_fin,
            ":id_aula" => $this->id_aula,
            ":permite_superposicion" => $this->permite_superposicion ? 1 : 0,
            ":id_horario" => $this->id_horario
        ]);
    }

    public function marcarClasesConjuntasRelacionadas($idHorarioVinculado = null)
    {
        if (!$this->permite_superposicion || !$this->id_horario) return true;

        $stmt = $this->conn->prepare("
            UPDATE horarios h
            INNER JOIN asignacion_docente ad_existente
                ON ad_existente.id_asignacion = h.id_asignacion
            INNER JOIN asignacion_docente ad_actual
                ON ad_actual.id_asignacion = :id_asignacion_actual
            SET h.permite_superposicion = 1
            WHERE h.id_horario <> :id_horario
              AND h.activo = 1
              AND h.dia_semana = :dia_semana
              AND h.hora_inicio < :hora_fin
              AND h.hora_fin > :hora_inicio
              AND h.id_grado IS NOT NULL
              AND h.id_grado <> :id_grado
              AND ad_existente.id_profesor = ad_actual.id_profesor
              AND (
                  (
                      :id_vinculado_presente = 1
                      AND h.id_horario = :id_horario_vinculado
                  )
                  OR (
                      :id_vinculado_ausente = 1
                      AND h.id_aula = :id_aula
                  )
              )
        ");

        return $stmt->execute([
            ":id_asignacion_actual" => $this->id_asignacion,
            ":id_horario" => $this->id_horario,
            ":dia_semana" => $this->dia_semana,
            ":hora_fin" => $this->hora_fin,
            ":hora_inicio" => $this->hora_inicio,
            ":id_grado" => $this->id_grado,
            ":id_vinculado_presente" => $idHorarioVinculado ? 1 : 0,
            ":id_horario_vinculado" => $idHorarioVinculado ?: 0,
            ":id_vinculado_ausente" => $idHorarioVinculado ? 0 : 1,
            ":id_aula" => $this->id_aula
        ]);
    }

    public function desactivar()
    {
        $this->desvincularHorario($this->id_horario);
        $stmt = $this->conn->prepare("
            UPDATE " . $this->table_name . "
            SET activo = 0
            WHERE id_horario = :id
        ");

        return $stmt->execute([":id" => $this->id_horario]);
    }

    public function restaurar()
    {
        $stmt = $this->conn->prepare("
            UPDATE " . $this->table_name . "
            SET activo = 1
            WHERE id_horario = :id
        ");

        return $stmt->execute([":id" => $this->id_horario]);
    }

    public function eliminar()
    {
        $this->desvincularHorario($this->id_horario);
        $stmt = $this->conn->prepare("
            DELETE FROM " . $this->table_name . "
            WHERE id_horario = :id
        ");
        $stmt->execute([":id" => $this->id_horario]);

        return $stmt->rowCount() > 0;
    }
}
