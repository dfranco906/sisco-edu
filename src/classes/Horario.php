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

    private function consultaListado($activo, ?int $id_profesor = null)
    {
        $sql = "
            SELECT
                h.id_horario,
                h.id_asignacion,
                COALESCE(h.id_grado, ad.id_grado) AS id_grado,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                COALESCE(h.id_aula, g.id_aula, aul_asig.id_aula) AS id_aula,
                h.activo,
                COALESCE(g.nombre, g_asig.nombre) AS grado,
                COALESCE(a.nombre, aul_asig.nombre) AS aula,
                m.nombre AS materia,
                CONCAT(p.nombre, ' ', p.apellido) AS profesor
            FROM horarios h
            LEFT JOIN asignacion_docente ad ON h.id_asignacion = ad.id_asignacion
            LEFT JOIN grados g ON h.id_grado = g.id_grado
            LEFT JOIN aulas a ON h.id_aula = a.id_aula
            LEFT JOIN grados g_asig ON ad.id_grado = g_asig.id_grado
            LEFT JOIN aulas aul_asig ON g_asig.id_aula = aul_asig.id_aula
            LEFT JOIN materias m ON ad.id_materia = m.id_materia
            LEFT JOIN profesores p ON ad.id_profesor = p.id_profesor
            WHERE h.activo = :activo
        ";
        $params = [":activo" => $activo];

        if ($id_profesor !== null) {
            $sql .= " AND ad.id_profesor = :id_profesor ";
            $params[":id_profesor"] = $id_profesor;
        }

        $sql .= "
            ORDER BY
                COALESCE(g.nombre, g_asig.nombre),
                CASE h.dia_semana 
                    WHEN 'Lunes' THEN 1 
                    WHEN 'Martes' THEN 2 
                    WHEN 'Miércoles' THEN 3 
                    WHEN 'Miercoles' THEN 3 
                    WHEN 'Jueves' THEN 4 
                    WHEN 'Viernes' THEN 5 
                    WHEN 'Sábado' THEN 6 
                    WHEN 'Sabado' THEN 6 
                    ELSE 7 
                END,
                h.hora_inicio
        ";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    public function leer(?int $id_profesor = null)
    {
        return $this->consultaListado(1, $id_profesor);
    }

    public function leerDesactivados(?int $id_profesor = null)
    {
        return $this->consultaListado(0, $id_profesor);
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
