<?php
declare(strict_types=1);

class Grado {
    private PDO $conn;

    public ?int $id_grado = null;
    public ?string $nombre = null;
    public ?int $id_aula = null;
    public ?int $id_turno = null;
    public ?int $id_nivel = null;
    public ?int $id_bachillerato = null;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO grados(nombre, id_aula, id_turno, id_nivel, id_bachillerato)
            VALUES(:nombre, :id_aula, :id_turno, :id_nivel, :id_bachillerato)
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula,
            ":id_turno" => $this->id_turno ?: null,
            ":id_nivel" => $this->id_nivel ?: null,
            ":id_bachillerato" => $this->id_bachillerato ?: null
        ]);
    }

    public function leer(?int $id_profesor = null): PDOStatement {
        if ($id_profesor !== null) {
            $stmt = $this->conn->prepare("
                SELECT DISTINCT
                    g.id_grado,
                    g.nombre,
                    g.id_aula,
                    COALESCE(a.nombre, 'Sin aula') AS aula,
                    g.id_turno,
                    COALESCE(t.nombre, 'Sin turno') AS turno,
                    g.id_nivel,
                    COALESCE(n.nombre, 'Sin nivel') AS nivel,
                    g.id_bachillerato,
                    COALESCE(b.codigo, 'N/A') AS bachillerato,
                    g.activo,
                    CONCAT(g.nombre, ' - ', COALESCE(a.nombre, 'Sin aula')) AS descripcion
                FROM grados g
                LEFT JOIN aulas a ON g.id_aula = a.id_aula
                LEFT JOIN turnos t ON g.id_turno = t.id_turno
                LEFT JOIN niveles_academicos n ON g.id_nivel = n.id_nivel
                LEFT JOIN bachilleratos b ON g.id_bachillerato = b.id_bachillerato
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
                COALESCE(a.nombre, 'Sin aula') AS aula,
                g.id_turno,
                COALESCE(t.nombre, 'Sin turno') AS turno,
                g.id_nivel,
                COALESCE(n.nombre, 'Sin nivel') AS nivel,
                g.id_bachillerato,
                COALESCE(b.codigo, 'N/A') AS bachillerato,
                g.activo,
                CONCAT(g.nombre, ' - ', COALESCE(a.nombre, 'Sin aula')) AS descripcion
            FROM grados g
            LEFT JOIN aulas a ON g.id_aula = a.id_aula
            LEFT JOIN turnos t ON g.id_turno = t.id_turno
            LEFT JOIN niveles_academicos n ON g.id_nivel = n.id_nivel
            LEFT JOIN bachilleratos b ON g.id_bachillerato = b.id_bachillerato
            WHERE g.activo = 1
            ORDER BY COALESCE(g.id_nivel, 99) ASC, g.nombre ASC
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
                COALESCE(a.nombre, 'Sin aula') AS aula,
                g.id_turno,
                COALESCE(t.nombre, 'Sin turno') AS turno,
                g.id_nivel,
                COALESCE(n.nombre, 'Sin nivel') AS nivel,
                g.id_bachillerato,
                COALESCE(b.codigo, 'N/A') AS bachillerato,
                g.activo,
                CONCAT(g.nombre, ' - ', COALESCE(a.nombre, 'Sin aula')) AS descripcion
            FROM grados g
            LEFT JOIN aulas a ON g.id_aula = a.id_aula
            LEFT JOIN turnos t ON g.id_turno = t.id_turno
            LEFT JOIN niveles_academicos n ON g.id_nivel = n.id_nivel
            LEFT JOIN bachilleratos b ON g.id_bachillerato = b.id_bachillerato
            WHERE g.activo = 0
            ORDER BY g.nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE grados
            SET nombre = :nombre,
                id_aula = :id_aula,
                id_turno = :id_turno,
                id_nivel = :id_nivel,
                id_bachillerato = :id_bachillerato
            WHERE id_grado = :id_grado
        ");

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":id_aula" => $this->id_aula,
            ":id_turno" => $this->id_turno ?: null,
            ":id_nivel" => $this->id_nivel ?: null,
            ":id_bachillerato" => $this->id_bachillerato ?: null,
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
