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

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function obtenerGradoActivo($id_grado)
    {
        $stmt = $this->conn->prepare("
            SELECT g.id_aula
            FROM grados g
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
                (id_asignacion, id_grado, dia_semana, hora_inicio, hora_fin, id_aula)
            VALUES
                (:id_asignacion, :id_grado, :dia_semana, :hora_inicio, :hora_fin, :id_aula)
        ");

        return $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":id_grado" => $this->id_grado,
            ":dia_semana" => $this->dia_semana,
            ":hora_inicio" => $this->hora_inicio,
            ":hora_fin" => $this->hora_fin,
            ":id_aula" => $this->id_aula
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
                h.activo,
                g.nombre AS grado,
                a.nombre AS aula,
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
                g.nombre,
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
                id_aula = :id_aula
            WHERE id_horario = :id_horario
        ");

        return $stmt->execute([
            ":id_asignacion" => $this->id_asignacion,
            ":id_grado" => $this->id_grado,
            ":dia_semana" => $this->dia_semana,
            ":hora_inicio" => $this->hora_inicio,
            ":hora_fin" => $this->hora_fin,
            ":id_aula" => $this->id_aula,
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