<?php
declare(strict_types=1);

require_once __DIR__ . '/IdentidadHelper.php';

class Profesor {
    private PDO $conn;
    private string $table_name = "profesores";

    public $nombre;
    public $apellido;
    public $cedula_identidad;
    public $user_id_global;
    public $id_profesor;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    public function crear(): bool {
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->apellido = htmlspecialchars(strip_tags((string)$this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags((string)$this->cedula_identidad));
        $this->user_id_global = !empty($this->user_id_global) ? $this->user_id_global : ('PROF_' . uniqid() . '_' . random_int(100, 999));

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Profesor',
            $this->cedula_identidad,
            $this->nombre,
            $this->apellido
        );

        $query = "INSERT INTO " . $this->table_name . " (nombre, apellido, cedula_identidad, user_id_global, id_usuario, activo)
                  VALUES (:nombre, :apellido, :cedula, :user_id_global, :id_usuario, 1)
                  RETURNING id_profesor";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_usuario", $idUsuario);

        if ($stmt->execute()) {
            $this->id_profesor = (int)$stmt->fetchColumn();
            return true;
        }
        return false;
    }

    public function leer(): PDOStatement {
        $query = "SELECT 
                    p.id_profesor,
                    p.nombre,
                    p.apellido,
                    p.cedula_identidad,
                    p.user_id_global,
                    p.id_usuario,
                    p.activo,
                    u.usuario,
                    u.email,
                    u.celular,
                    CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                  FROM profesores p
                  LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
                  WHERE p.activo = 1
                  ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados(): PDOStatement {
        $query = "SELECT 
                    p.id_profesor,
                    p.nombre,
                    p.apellido,
                    p.cedula_identidad,
                    p.user_id_global,
                    p.id_usuario,
                    p.activo,
                    u.usuario,
                    u.email,
                    u.celular,
                    CONCAT(p.nombre, ' ', p.apellido) AS nombre_completo
                  FROM profesores p
                  LEFT JOIN usuarios u ON p.id_usuario = u.id_usuario
                  WHERE p.activo = 0
                  ORDER BY p.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar(): bool {
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->apellido = htmlspecialchars(strip_tags((string)$this->apellido));
        $this->cedula_identidad = htmlspecialchars(strip_tags((string)$this->cedula_identidad));
        $this->user_id_global = !empty($this->user_id_global) ? $this->user_id_global : NULL;
        $this->id_profesor = (int)$this->id_profesor;

        $idUsuario = IdentidadHelper::asegurarUsuario(
            $this->conn,
            'Profesor',
            $this->cedula_identidad,
            $this->nombre,
            $this->apellido
        );

        $query = "UPDATE " . $this->table_name . "
                  SET nombre = :nombre,
                      apellido = :apellido,
                      cedula_identidad = :cedula,
                      user_id_global = :user_id_global,
                      id_usuario = COALESCE(id_usuario, :id_usuario)
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":cedula", $this->cedula_identidad);
        $stmt->bindParam(":user_id_global", $this->user_id_global);
        $stmt->bindParam(":id_usuario", $idUsuario);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function desactivar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function restaurar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);

        return $stmt->execute();
    }

    public function contarDependencias(): int {
        $query = "SELECT
                    (SELECT COUNT(*) FROM asignacion_docente WHERE id_profesor=:id1) +
                    (SELECT COUNT(*) FROM asistencias_profesores WHERE id_profesor=:id2) +
                    (SELECT COUNT(*) FROM huellas_templates WHERE id_profesor=:id3)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":id1" => $this->id_profesor,
            ":id2" => $this->id_profesor,
            ":id3" => $this->id_profesor
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function eliminar(): bool {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_profesor = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_profesor);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
