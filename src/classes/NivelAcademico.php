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

    public function crear(): array {
        $nombreLimpio = trim((string)$this->nombre);
        $codigoLimpio = !empty($this->codigo) ? strtoupper(trim((string)$this->codigo)) : null;

        if (empty($nombreLimpio)) {
            return ['ok' => false, 'code' => 400, 'message' => "El nombre del nivel no puede estar vacío."];
        }

        // Verificar existencia por nombre
        $stmtCheck = $this->conn->prepare("SELECT id_nivel, activo FROM {$this->table_name} WHERE LOWER(nombre) = LOWER(:nombre)");
        $stmtCheck->execute([':nombre' => $nombreLimpio]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if ((int)$existente['activo'] === 1) {
                return ['ok' => false, 'code' => 409, 'message' => "Ya existe un nivel académico activo con el nombre '{$nombreLimpio}'."];
            } else {
                $stmtReactivar = $this->conn->prepare("
                    UPDATE {$this->table_name}
                    SET activo = 1, codigo = :codigo, descripcion = :descripcion
                    WHERE id_nivel = :id
                ");
                $ok = $stmtReactivar->execute([
                    ':codigo' => $codigoLimpio,
                    ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
                    ':id' => $existente['id_nivel']
                ]);
                return [
                    'ok' => $ok,
                    'code' => 200,
                    'message' => $ok ? "El nivel '{$nombreLimpio}' estaba en registros desactivados y fue reactivado exitosamente." : "Error al reactivar el nivel."
                ];
            }
        }

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_name} (nombre, codigo, descripcion, activo)
            VALUES (:nombre, :codigo, :descripcion, 1)
        ");
        $ok = $stmt->execute([
            ':nombre' => $nombreLimpio,
            ':codigo' => $codigoLimpio,
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
        ]);

        return [
            'ok' => $ok,
            'code' => $ok ? 201 : 400,
            'message' => $ok ? "Nivel académico creado correctamente" : "Error al crear el nivel académico."
        ];
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
