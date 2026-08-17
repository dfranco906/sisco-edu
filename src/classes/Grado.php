<?php
class Grado {
    private $conn;

    public $id_grado;
    public $nombre;
    public $id_aula;
    public $room_id;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function obtenerCodigoAula() {
        $stmt = $this->conn->prepare("
            SELECT codigo
            FROM aulas
            WHERE id_aula = :id_aula
              AND activo = 1
            LIMIT 1
        ");
        $stmt->execute([":id_aula" => $this->id_aula]);
        return $stmt->fetchColumn() ?: null;
    }

    public function aulaActivaExiste() {
        return $this->obtenerCodigoAula() !== null;
    }

    public function crear() {
        $this->room_id = $this->obtenerCodigoAula();
        if (!$this->room_id) return false;

        $stmt = $this->conn->prepare("
            INSERT INTO grados(nombre, id_aula, room_id)
            VALUES(:nombre, :id_aula, :room_id)
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula,
            ":room_id" => $this->room_id
        ]);
    }

    public function leer() {
        $stmt = $this->conn->prepare("
            SELECT 
                g.id_grado,
                g.nombre,
                g.id_aula,
                a.nombre AS aula,
                a.codigo AS codigo_aula,
                COALESCE(a.activo, 0) AS aula_activa,
                g.activo,
                CONCAT(g.nombre, ' - ', COALESCE(a.nombre, 'Aula no disponible')) AS descripcion
            FROM grados g
            LEFT JOIN aulas a ON g.id_aula = a.id_aula
            WHERE g.activo = 1
            ORDER BY g.nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function leerOpciones() {
        $stmt = $this->conn->prepare("
            SELECT
                g.id_grado,
                g.nombre,
                g.id_aula,
                a.nombre AS aula,
                a.codigo AS codigo_aula,
                1 AS aula_activa,
                g.activo,
                CONCAT(g.nombre, ' - ', a.nombre) AS descripcion
            FROM grados g
            INNER JOIN aulas a ON g.id_aula = a.id_aula AND a.activo = 1
            WHERE g.activo = 1
            ORDER BY g.nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $stmt = $this->conn->prepare("
            SELECT 
                g.id_grado,
                g.nombre,
                g.id_aula,
                a.nombre AS aula,
                a.codigo AS codigo_aula,
                COALESCE(a.activo, 0) AS aula_activa,
                g.activo,
                CONCAT(g.nombre, ' - ', COALESCE(a.nombre, 'Aula no disponible')) AS descripcion
            FROM grados g
            LEFT JOIN aulas a ON g.id_aula = a.id_aula
            WHERE g.activo = 0
            ORDER BY g.nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $this->room_id = $this->obtenerCodigoAula();
        if (!$this->room_id) return false;

        $stmt = $this->conn->prepare("
            UPDATE grados
            SET nombre=:nombre,
                id_aula=:id_aula,
                room_id=:room_id
            WHERE id_grado=:id_grado
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula,
            ":room_id" => $this->room_id,
            ":id_grado" => $this->id_grado
        ]);
    }

    public function desactivar() {
        $stmt = $this->conn->prepare("
            UPDATE grados SET activo = 0 WHERE id_grado = :id_grado
        ");

        return $stmt->execute([":id_grado" => $this->id_grado]);
    }

    public function restaurar() {
        $stmt = $this->conn->prepare("
            UPDATE grados SET activo = 1 WHERE id_grado = :id_grado
        ");

        return $stmt->execute([":id_grado" => $this->id_grado]);
    }

    public function contarEstudiantesAsociados() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM estudiantes
            WHERE id_grado = :id_grado
        ");
        $stmt->execute([":id_grado" => $this->id_grado]);

        return (int) $stmt->fetchColumn();
    }

    public function eliminar() {
        $stmt = $this->conn->prepare("
            DELETE FROM grados WHERE id_grado = :id_grado
        ");
        $stmt->execute([":id_grado" => $this->id_grado]);

        return $stmt->rowCount() > 0;
    }
}
?>
