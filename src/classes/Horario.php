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
                g.id_aula
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

    public function obtenerConflicto($excluirId = null, $permitirClaseConjunta = false)
    {
        $query = "SELECT
                    h.id_horario,
                    CASE
                        WHEN h.id_grado = :id_grado_tipo THEN 'grado'
                        WHEN h.id_aula = :id_aula_tipo THEN 'aula'
                        ELSE 'profesor'
                    END AS tipo,
                    CASE
                        WHEN h.id_grado IS NOT NULL
                         AND h.id_grado <> :id_grado_excepcion_info
                         AND h.id_aula = :id_aula_excepcion_info
                         AND ad_existente.id_profesor = ad_nueva.id_profesor
                        THEN 1 ELSE 0
                    END AS excepcion_disponible
                  FROM horarios h
                  INNER JOIN asignacion_docente ad_existente
                    ON ad_existente.id_asignacion = h.id_asignacion
                  INNER JOIN asignacion_docente ad_nueva
                    ON ad_nueva.id_asignacion = :id_asignacion_nueva
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
                        AND h.id_aula = :id_aula_excepcion
                        AND ad_existente.id_profesor = ad_nueva.id_profesor
                    )";

        $params = [
            ":id_grado_tipo" => $this->id_grado,
            ":id_aula_tipo" => $this->id_aula,
            ":id_grado_excepcion_info" => $this->id_grado,
            ":id_aula_excepcion_info" => $this->id_aula,
            ":id_asignacion_nueva" => $this->id_asignacion,
            ":dia_semana" => $this->dia_semana,
            ":hora_fin" => $this->hora_fin,
            ":hora_inicio" => $this->hora_inicio,
            ":id_grado_conflicto" => $this->id_grado,
            ":id_aula_conflicto" => $this->id_aula,
            ":permitir_superposicion" => $permitirClaseConjunta ? 1 : 0,
            ":id_grado_excepcion" => $this->id_grado,
            ":id_aula_excepcion" => $this->id_aula
        ];

        if ($excluirId !== null) {
            $query .= " AND h.id_horario <> :excluir_id";
            $params[":excluir_id"] = $excluirId;
        }

        $query .= " ORDER BY h.hora_inicio LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
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

    public function marcarClasesConjuntasRelacionadas()
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
              AND h.id_aula = :id_aula
              AND ad_existente.id_profesor = ad_actual.id_profesor
        ");

        return $stmt->execute([
            ":id_asignacion_actual" => $this->id_asignacion,
            ":id_horario" => $this->id_horario,
            ":dia_semana" => $this->dia_semana,
            ":hora_fin" => $this->hora_fin,
            ":hora_inicio" => $this->hora_inicio,
            ":id_grado" => $this->id_grado,
            ":id_aula" => $this->id_aula
        ]);
    }

    public function desactivar()
    {
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
        $stmt = $this->conn->prepare("
            DELETE FROM " . $this->table_name . "
            WHERE id_horario = :id
        ");
        $stmt->execute([":id" => $this->id_horario]);

        return $stmt->rowCount() > 0;
    }
}
