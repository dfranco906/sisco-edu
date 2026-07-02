<?php

class Horario
{
    private $conn;
    private $table_name = "horarios";

    public $id_horario;
    public $id_asignacion;
    public $grado;
    public $dia_semana;
    public $hora_inicio;
    public $hora_fin;
    public $aula;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerGradoActivo($id_grado)
    {
        $stmt = $this->conn->prepare("
            SELECT
                g.id_grado,
                g.nombre,
                NULLIF(TRIM(g.room_id), '') AS room_id,
                a.nombre AS aula_nombre,
                a.codigo AS aula_codigo,
                CASE
                    WHEN a.id_aula IS NOT NULL
                     AND a.activo = 1
                     AND a.codigo = g.room_id THEN 1
                    ELSE 0
                END AS aula_valida
            FROM grados g
            LEFT JOIN aulas a ON a.id_aula = g.id_aula
            WHERE g.id_grado = :id_grado
              AND g.activo = 1
            LIMIT 1
        ");
        $stmt->execute([":id_grado" => $id_grado]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function asignacionActivaExiste($id_asignacion)
    {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM asignacion_docente
            WHERE id_asignacion = :id_asignacion
              AND activo = 1
        ");
        $stmt->execute([":id_asignacion" => $id_asignacion]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function crear()
    {
        $stmt = $this->conn->prepare("
            INSERT INTO horarios
                (id_asignacion, grado, dia_semana, hora_inicio, hora_fin, aula)
            VALUES
                (:id_asignacion, :grado, :dia_semana, :hora_inicio, :hora_fin, :aula)
        ");

        return $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":grado" => $this->grado,
            ":dia_semana" => $this->dia_semana,
            ":hora_inicio" => $this->hora_inicio,
            ":hora_fin" => $this->hora_fin,
            ":aula" => $this->aula
        ]);
    }

    private function consultaListado($activo)
    {
        $stmt = $this->conn->prepare("
            SELECT
                h.id_horario,
                h.id_asignacion,
                COALESCE(
                    (
                        SELECT g1.id_grado
                        FROM grados g1
                        WHERE g1.activo = 1
                          AND g1.nombre = h.grado
                          AND g1.room_id = h.aula
                        ORDER BY g1.id_grado
                        LIMIT 1
                    ),
                    (
                        SELECT CASE WHEN COUNT(*) = 1 THEN MIN(g2.id_grado) ELSE NULL END
                        FROM grados g2
                        WHERE g2.activo = 1
                          AND g2.nombre = h.grado
                    )
                ) AS id_grado,
                h.grado,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                h.aula,
                h.aula AS room_id,
                h.activo,
                m.nombre AS materia,
                CONCAT(p.nombre, ' ', p.apellido) AS profesor
            FROM horarios h
            LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
            LEFT JOIN materias m ON ad.id_materia = m.id_materia
            LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
            WHERE h.activo = :activo
            ORDER BY
                h.grado,
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
                grado = :grado,
                dia_semana = :dia_semana,
                hora_inicio = :hora_inicio,
                hora_fin = :hora_fin,
                aula = :aula
            WHERE id_horario = :id_horario
        ");

        return $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":grado" => $this->grado,
            ":dia_semana" => $this->dia_semana,
            ":hora_inicio" => $this->hora_inicio,
            ":hora_fin" => $this->hora_fin,
            ":aula" => $this->aula,
            ":id_horario" => $this->id_horario
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
