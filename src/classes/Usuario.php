<?php
declare(strict_types=1);

class Usuario {
    private PDO $conn;
    private string $table_name = "usuarios";

    public $nombre;
    public $apellido;
    public $usuario;
    public $email;
    public $celular;
    public $password;
    public $rol;
    public $id_usuario;

    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    // ✅ CREATE
    public function crear(): bool {
        $query = "INSERT INTO " . $this->table_name . " (nombre, apellido, usuario, email, celular, password, rol)
                  VALUES (:nombre, :apellido, :usuario, :email, :celular, :password, :rol)
                  RETURNING id_usuario";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->apellido = htmlspecialchars(strip_tags((string)$this->apellido));
        $this->usuario = htmlspecialchars(strip_tags((string)$this->usuario));
        $this->email = htmlspecialchars(strip_tags((string)$this->email));
        $this->celular = htmlspecialchars(strip_tags((string)$this->celular));
        $this->rol = htmlspecialchars(strip_tags((string)$this->rol));

        // 🔐 Encriptar contraseña
        $this->password = password_hash((string)$this->password, PASSWORD_DEFAULT);

        // Bind
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":usuario", $this->usuario);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":rol", $this->rol);

        if ($stmt->execute()) {
            $this->id_usuario = (int)$stmt->fetchColumn();

            // Auto-vincular o auto-crear perfil específico correspondiente
            if ($this->rol === 'Profesor') {
                $stP = $this->conn->prepare("UPDATE profesores SET id_usuario = :u WHERE cedula_identidad = :ci AND id_usuario IS NULL");
                $stP->execute([':u' => $this->id_usuario, ':ci' => $this->usuario]);
                if ($stP->rowCount() === 0) {
                    $stCheck = $this->conn->prepare("SELECT id_profesor FROM profesores WHERE id_usuario = :u");
                    $stCheck->execute([':u' => $this->id_usuario]);
                    if (!$stCheck->fetch()) {
                        $stIns = $this->conn->prepare("INSERT INTO profesores (nombre, apellido, cedula_identidad, id_usuario, activo) VALUES (:n, :a, :ci, :u, 1)");
                        $stIns->execute([':n' => $this->nombre, ':a' => $this->apellido, ':ci' => $this->usuario, ':u' => $this->id_usuario]);
                    }
                }
            } elseif ($this->rol === 'Estudiante') {
                $stE = $this->conn->prepare("UPDATE estudiantes SET id_usuario = :u WHERE cedula_identidad = :ci AND id_usuario IS NULL");
                $stE->execute([':u' => $this->id_usuario, ':ci' => $this->usuario]);
                if ($stE->rowCount() === 0) {
                    $stCheck = $this->conn->prepare("SELECT id_estudiante FROM estudiantes WHERE id_usuario = :u");
                    $stCheck->execute([':u' => $this->id_usuario]);
                    if (!$stCheck->fetch()) {
                        $stIns = $this->conn->prepare("INSERT INTO estudiantes (nombre, apellido, cedula_identidad, id_usuario, activo) VALUES (:n, :a, :ci, :u, 1)");
                        $stIns->execute([':n' => $this->nombre, ':a' => $this->apellido, ':ci' => $this->usuario, ':u' => $this->id_usuario]);
                    }
                }
            } elseif ($this->rol === 'Tutor') {
                $stT = $this->conn->prepare("UPDATE tutores SET id_usuario = :u WHERE cedula_identidad = :ci AND id_usuario IS NULL");
                $stT->execute([':u' => $this->id_usuario, ':ci' => $this->usuario]);
                if ($stT->rowCount() === 0) {
                    $stCheck = $this->conn->prepare("SELECT id_tutor FROM tutores WHERE id_usuario = :u");
                    $stCheck->execute([':u' => $this->id_usuario]);
                    if (!$stCheck->fetch()) {
                        $stIns = $this->conn->prepare("INSERT INTO tutores (nombre, apellido, cedula_identidad, celular, email, id_usuario, activo) VALUES (:n, :a, :ci, :cel, :em, :u, 1)");
                        $stIns->execute([':n' => $this->nombre, ':a' => $this->apellido, ':ci' => $this->usuario, ':cel' => $this->celular, ':em' => $this->email, ':u' => $this->id_usuario]);
                    }
                }
            }

            return true;
        }

        return false;
    }

    // ✅ READ
    public function leer(): PDOStatement {
        $query = "SELECT id_usuario, nombre, apellido, usuario, email, celular, rol, fecha_creacion, activo
                  FROM " . $this->table_name . "
                  WHERE activo = 1
                  ORDER BY id_usuario DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function leerDesactivados(): PDOStatement {
        $query = "SELECT id_usuario, nombre, apellido, usuario, email, celular, rol, fecha_creacion, activo
                  FROM " . $this->table_name . "
                  WHERE activo = 0
                  ORDER BY id_usuario DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function actualizar(): bool {
        if (!empty($this->password)) {
            $hashed = password_hash((string)$this->password, PASSWORD_DEFAULT);
            $query = "UPDATE " . $this->table_name . "
                      SET nombre=:nombre,
                          apellido=:apellido,
                          usuario=:usuario,
                          email=:email,
                          celular=:celular,
                          rol=:rol,
                          password=:password
                      WHERE id_usuario=:id_usuario";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":password", $hashed);
        } else {
            $query = "UPDATE " . $this->table_name . "
                      SET nombre=:nombre,
                          apellido=:apellido,
                          usuario=:usuario,
                          email=:email,
                          celular=:celular,
                          rol=:rol
                      WHERE id_usuario=:id_usuario";
            $stmt = $this->conn->prepare($query);
        }

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":usuario", $this->usuario);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":rol", $this->rol);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        return $stmt->execute();
    }

    public function desactivar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 0
                  WHERE id_usuario = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_usuario);

        return $stmt->execute();
    }

    public function restaurar(): bool {
        $query = "UPDATE " . $this->table_name . "
                  SET activo = 1
                  WHERE id_usuario = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_usuario);

        return $stmt->execute();
    }

    public function eliminar(): bool {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE id_usuario = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_usuario);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
