<?php
class Bachillerato {
    private PDO $conn;
    private string $table_name = "bachilleratos";

    public $id_bachillerato;
    public $nombre;
    public $codigo;
    public $tipo;
    public $descripcion;
    public $activo;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function leer() {
        $stmt = $this->conn->prepare("
            SELECT id_bachillerato, nombre, codigo, tipo, descripcion, activo, fecha_registro
            FROM {$this->table_name}
            WHERE activo = 1
            ORDER BY tipo ASC, nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $stmt = $this->conn->prepare("
            SELECT id_bachillerato, nombre, codigo, tipo, descripcion, activo, fecha_registro
            FROM {$this->table_name}
            WHERE activo = 0
            ORDER BY nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function crear(): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_name} (nombre, codigo, tipo, descripcion, activo)
            VALUES (:nombre, :codigo, :tipo, :descripcion, 1)
        ");
        return $stmt->execute([
            ':nombre' => trim((string)$this->nombre),
            ':codigo' => strtoupper(trim((string)$this->codigo)),
            ':tipo' => !empty($this->tipo) ? trim((string)$this->tipo) : 'Técnico',
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
        ]);
    }

    public function actualizar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET nombre = :nombre,
                codigo = :codigo,
                tipo = :tipo,
                descripcion = :descripcion
            WHERE id_bachillerato = :id
        ");
        return $stmt->execute([
            ':nombre' => trim((string)$this->nombre),
            ':codigo' => strtoupper(trim((string)$this->codigo)),
            ':tipo' => !empty($this->tipo) ? trim((string)$this->tipo) : 'Técnico',
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
            ':id' => $this->id_bachillerato
        ]);
    }

    public function desactivar(): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 0 WHERE id_bachillerato = :id");
        return $stmt->execute([':id' => $this->id_bachillerato]);
    }

    public function restaurar(): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 1 WHERE id_bachillerato = :id");
        return $stmt->execute([':id' => $this->id_bachillerato]);
    }

    public function eliminar(): bool {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_bachillerato = :id");
        return $stmt->execute([':id' => $this->id_bachillerato]);
    }
}
