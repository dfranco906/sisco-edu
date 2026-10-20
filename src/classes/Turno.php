<?php
class Turno {
    private PDO $conn;
    private string $table_name = "turnos";

    public $id_turno;
    public $nombre;
    public $hora_inicio;
    public $hora_fin;
    public $descripcion;
    public $activo;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function leer() {
        $stmt = $this->conn->prepare("
            SELECT id_turno, nombre, hora_inicio, hora_fin, descripcion, activo, fecha_registro
            FROM {$this->table_name}
            WHERE activo = 1
            ORDER BY hora_inicio ASC, nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $stmt = $this->conn->prepare("
            SELECT id_turno, nombre, hora_inicio, hora_fin, descripcion, activo, fecha_registro
            FROM {$this->table_name}
            WHERE activo = 0
            ORDER BY nombre ASC
        ");
        $stmt->execute();
        return $stmt;
    }

    public function crear(): bool {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_name} (nombre, hora_inicio, hora_fin, descripcion, activo)
            VALUES (:nombre, :hora_inicio, :hora_fin, :descripcion, 1)
        ");
        return $stmt->execute([
            ':nombre' => trim((string)$this->nombre),
            ':hora_inicio' => !empty($this->hora_inicio) ? $this->hora_inicio : null,
            ':hora_fin' => !empty($this->hora_fin) ? $this->hora_fin : null,
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
        ]);
    }

    public function actualizar(): bool {
        $stmt = $this->conn->prepare("
            UPDATE {$this->table_name}
            SET nombre = :nombre,
                hora_inicio = :hora_inicio,
                hora_fin = :hora_fin,
                descripcion = :descripcion
            WHERE id_turno = :id
        ");
        return $stmt->execute([
            ':nombre' => trim((string)$this->nombre),
            ':hora_inicio' => !empty($this->hora_inicio) ? $this->hora_inicio : null,
            ':hora_fin' => !empty($this->hora_fin) ? $this->hora_fin : null,
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
            ':id' => $this->id_turno
        ]);
    }

    public function desactivar(): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 0 WHERE id_turno = :id");
        return $stmt->execute([':id' => $this->id_turno]);
    }

    public function restaurar(): bool {
        $stmt = $this->conn->prepare("UPDATE {$this->table_name} SET activo = 1 WHERE id_turno = :id");
        return $stmt->execute([':id' => $this->id_turno]);
    }

    public function eliminar(): bool {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table_name} WHERE id_turno = :id");
        return $stmt->execute([':id' => $this->id_turno]);
    }
}
