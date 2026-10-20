<?php
class NivelAcademico {
    private PDO $conn;
    private string $table_name = "niveles_academicos";

    public $id_nivel;
    public $nombre;
    public $codigo;
    public $descripcion;
    public $activo;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function leer() {
        $stmt = $this->conn->prepare("
            SELECT id_nivel, nombre, codigo, descripcion, activo, fecha_registro
            FROM {$this->table_name}
            WHERE activo = 1
            ORDER BY id_nivel ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $stmt = $this->conn->prepare("
            SELECT id_nivel, nombre, codigo, descripcion, activo, fecha_registro
            FROM {$this->table_name}
            WHERE activo = 0
            ORDER BY nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function crear(): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_name} (nombre, codigo, descripcion, activo)
            VALUES (:nombre, :codigo, :descripcion, 1)
        ");
        return $stmt->execute([
            ':nombre' => trim((string)$this->nombre),
            ':codigo' => !empty($this->codigo) ? strtoupper(trim((string)$this->codigo)) : null,
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
        ]);
    }

    public function actualizar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET nombre = :nombre,
                codigo = :codigo,
                descripcion = :descripcion
            WHERE id_nivel = :id
        ");
        return $stmt->execute([
            ':nombre' => trim((string)$this->nombre),
            ':codigo' => !empty($this->codigo) ? strtoupper(trim((string)$this->codigo)) : null,
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
            ':id' => $this->id_nivel
        ]);
    }

    public function desactivar(): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 0 WHERE id_nivel = :id");
        return $stmt->execute([':id' => $this->id_nivel]);
    }

    public function restaurar(): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 1 WHERE id_nivel = :id");
        return $stmt->execute([':id' => $this->id_nivel]);
    }

    public function eliminar(): bool {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_nivel = :id");
        return $stmt->execute([':id' => $this->id_nivel]);
    }
}
