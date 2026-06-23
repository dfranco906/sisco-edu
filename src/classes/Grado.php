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

    private function obtenerRoomIdAula() {
        $stmt = $this->conn->prepare("
            SELECT codigo FROM aulas 
            WHERE id_aula = :id_aula AND activo = 1
        ");
        $stmt->execute([":id_aula" => $this->id_aula]);
        $aula = $stmt->fetch(PDO::FETCH_ASSOC);

        return $aula ? $aula["codigo"] : null;
    }

    public function crear() {
        $this->room_id = $this->obtenerRoomIdAula();
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
                g.room_id,
                g.activo,
                CONCAT(g.nombre, ' - ', a.nombre, ' - ', g.room_id) AS descripcion
            FROM grados g
            INNER JOIN aulas a ON g.id_aula = a.id_aula
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
                g.room_id,
                g.activo,
                CONCAT(g.nombre, ' - ', a.nombre, ' - ', g.room_id) AS descripcion
            FROM grados g
            INNER JOIN aulas a ON g.id_aula = a.id_aula
            WHERE g.activo = 0
            ORDER BY g.nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $this->room_id = $this->obtenerRoomIdAula();
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
