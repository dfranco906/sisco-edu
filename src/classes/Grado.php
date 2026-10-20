<?php
declare(strict_types=1);

class Grado {
    private PDO $conn;

    public ?int $id_grado = null;
    public ?string $nombre = null;
    public ?int $id_aula = null;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO grados(nombre, id_aula)
            VALUES(:nombre, :id_aula)
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula
        ]);
    }

    public function leer(?int $id_profesor = null): PDOStatement {
        if ($id_profesor !== null) {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT
                    g.id_grado,
                    g.nombre,
                    g.id_aula,
                    a.nombre AS aula,
                    g.activo,
                    CONCAT(g.nombre, ' - ', a.nombre) AS descripcion
                FROM grados g
                INNER JOIN aulas a ON g.id_aula = a.id_aula
                INNER JOIN asignacion_docente ad ON ad.id_grado = g.id_grado
                WHERE g.activo = 1 AND ad.id_profesor = :id_profesor AND ad.activo = 1
                ORDER BY g.nombre ASC
            ");
            $stmt->execute([':id_profesor' => $id_profesor]);
            return $stmt;
        }

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

    public function leerDesactivados(): PDOStatement {
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

    public function actualizar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE grados
            SET nombre = :nombre, id_aula = :id_aula
            WHERE id_grado = :id_grado
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula,
            ":id_grado" => $this->id_grado
        ]);
    }

    public function desactivar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE grados
            SET activo = 0
            WHERE id_grado = :id_grado
        ");

        return $stmt->execute([
            ":id_grado" => $this->id_grado
        ]);
    }

    public function restaurar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE grados
            SET activo = 1
            WHERE id_grado = :id_grado
        ");

        return $stmt->execute([
            ":id_grado" => $this->id_grado
        ]);
    }

    public function contarDependencias(): int {
        $stmt = $this->conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM horarios WHERE id_grado = :id_grado) +
                (SELECT COUNT(*) FROM estudiantes WHERE id_grado = :id_grado) as total
        ");
        $stmt->execute([":id_grado" => $this->id_grado]);
        return (int) $stmt->fetchColumn();
    }

    public function eliminar(): bool {
        $stmt = $this->conn->prepare("DELETE FROM grados WHERE id_grado = :id_grado");
        return $stmt->execute([":id_grado" => $this->id_grado]);
    }
}
