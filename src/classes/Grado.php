<?php
class Grado {
    private $conn;

    public $id_grado;
    public $nombre;
    public $id_aula;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $stmt = $this->conn->prepare("
            INSERT INTO grados(nombre, id_aula)
            VALUES(:nombre, :id_aula)
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula
        ]);
    }

    public function leer() {
        $stmt = $this->conn->prepare("
            SELECT 
                g.id_grado,
                g.nombre,
                g.id_aula,
                a.nombre AS aula,
                g.activo,
                CONCAT(g.nombre, ' - ', a.nombre) AS descripcion
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
                g.activo,
                CONCAT(g.nombre, ' - ', a.nombre) AS descripcion
            FROM grados g
            INNER JOIN aulas a ON g.id_aula = a.id_aula
            WHERE g.activo = 0
            ORDER BY g.nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        $stmt = $this->conn->prepare("
            UPDATE grados
            SET nombre=:nombre,
                id_aula=:id_aula
            WHERE id_grado=:id_grado
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula,
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