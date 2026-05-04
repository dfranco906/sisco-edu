<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";

    public $nombre;
    public $apellido;
    public $usuario;
    public $email;
    public $celular;
    public $password;
    public $rol;
    public $id_usuario;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ✅ CREATE
    public function crear() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nombre=:nombre,
                      apellido=:apellido,
                      usuario=:usuario,
                      email=:email,
                      celular=:celular,
                      password=:password,
                      rol=:rol";

        $stmt = $this->conn->prepare($query);

        // Sanitizar
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->apellido = htmlspecialchars(strip_tags($this->apellido));
        $this->usuario = htmlspecialchars(strip_tags($this->usuario));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->celular = htmlspecialchars(strip_tags($this->celular));
        $this->rol = htmlspecialchars(strip_tags($this->rol));

        // 🔐 Encriptar contraseña
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":apellido", $this->apellido);
        $stmt->bindParam(":usuario", $this->usuario);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":celular", $this->celular);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":rol", $this->rol);

        return $stmt->execute();
    }

    // ✅ READ
    public function leer() {
        $query = "SELECT id_usuario, nombre, apellido, usuario, email, celular, rol, fecha_creacion
                  FROM " . $this->table_name . "
                  ORDER BY id_usuario DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }
    public function actualizar() {
    $query = "UPDATE " . $this->table_name . "
              SET nombre=:nombre,
                  apellido=:apellido,
                  usuario=:usuario,
                  email=:email,
                  celular=:celular,
                  rol=:rol
              WHERE id_usuario=:id_usuario";

    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(":nombre", $this->nombre);
    $stmt->bindParam(":apellido", $this->apellido);
    $stmt->bindParam(":usuario", $this->usuario);
    $stmt->bindParam(":email", $this->email);
    $stmt->bindParam(":celular", $this->celular);
    $stmt->bindParam(":rol", $this->rol);
    $stmt->bindParam(":id_usuario", $this->id_usuario);

    return $stmt->execute();
}
}
?>