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

    public function crear(): array {
        $nombreLimpio = trim((string)$this->nombre);
        $codigoLimpio = strtoupper(trim((string)$this->codigo));

        if (empty($nombreLimpio) || empty($codigoLimpio)) {
            return ['ok' => false, 'code' => 400, 'message' => "El nombre y el código del bachillerato son obligatorios."];
        }

        // Verificar existencia por código
        $stmtCheck = $this->conn->prepare("SELECT id_bachillerato, activo FROM {$this->table_name} WHERE UPPER(codigo) = :codigo OR LOWER(nombre) = LOWER(:nombre)");
        $stmtCheck->execute([':codigo' => $codigoLimpio, ':nombre' => $nombreLimpio]);
        $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            if ((int)$existente['activo'] === 1) {
                return ['ok' => false, 'code' => 409, 'message' => "Ya existe una especialidad activa con el código '{$codigoLimpio}' o nombre '{$nombreLimpio}'."];
            } else {
                $stmtReactivar = $this->conn->prepare("
                    UPDATE {$this->table_name}
                    SET activo = 1, nombre = :nombre, tipo = :tipo, descripcion = :descripcion
                    WHERE id_bachillerato = :id
                ");
                $ok = $stmtReactivar->execute([
                    ':nombre' => $nombreLimpio,
                    ':tipo' => !empty($this->tipo) ? trim((string)$this->tipo) : 'Técnico',
                    ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
                    ':id' => $existente['id_bachillerato']
                ]);
                return [
                    'ok' => $ok,
                    'code' => 200,
                    'message' => $ok ? "La especialidad '{$codigoLimpio}' estaba desactivada y fue reactivada exitosamente." : "Error al reactivar la especialidad."
                ];
            }
        }

        $stmt = $this->conn->prepare("
            INSERT INTO {$this->table_name} (nombre, codigo, tipo, descripcion, activo)
            VALUES (:nombre, :codigo, :tipo, :descripcion, 1)
        ");
        $ok = $stmt->execute([
            ':nombre' => $nombreLimpio,
            ':codigo' => $codigoLimpio,
            ':tipo' => !empty($this->tipo) ? trim((string)$this->tipo) : 'Técnico',
            ':descripcion' => !empty($this->descripcion) ? trim((string)$this->descripcion) : null,
        ]);

        return [
            'ok' => $ok,
            'code' => $ok ? 201 : 400,
            'message' => $ok ? "Bachillerato creado correctamente" : "Error al registrar el bachillerato."
        ];
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
