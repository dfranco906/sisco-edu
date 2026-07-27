<?php
class Estudiante {
    private $conn;

    public $id_estudiante, $nombre, $apellido, $cedula_identidad;
    public $id_grado;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function crear() {
        $query = "INSERT INTO estudiantes
                  SET nombre=:nombre,
                      apellido=:apellido,
                      cedula_identidad=:cedula,
                      id_grado=:id_grado";

        $stmt = $this->conn->prepare($query);

        return $stmt->execute([
            ":nombre" => $this->nombre,
            ":apellido" => $this->apellido,
            ":cedula" => $this->cedula_identidad,
            ":id_grado" => $this->id_grado
        ]);
    }

    public function leer() {
        $query = "SELECT 
                    e.id_estudiante,
                    e.nombre,
                    e.apellido,
                    e.cedula_identidad,
                    e.user_id_global,
                    g.nombre AS grado,
                    e.activo,
                    CONCAT(e.nombre, ' ', e.apellido, ' - CI: ', e.cedula_identidad) AS nombre_completo
                  FROM estudiantes e
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  WHERE e.activo = 1
                  ORDER BY e.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function leerDesactivados() {
        $query = "SELECT 
                    e.id_estudiante,
                    e.nombre,
                    e.apellido,
                    e.cedula_identidad,
                    e.user_id_global,
                    g.nombre AS grado,
                    e.activo,
                    CONCAT(e.nombre, ' ', e.apellido, ' - CI: ', e.cedula_identidad) AS nombre_completo
                  FROM estudiantes e
                  LEFT JOIN grados g ON e.id_grado = g.id_grado
                  WHERE e.activo = 0
                  ORDER BY e.nombre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function actualizar() {
        if (empty($this->id_estudiante)) return false;

        $campos = [
            "nombre=:nombre",
            "apellido=:apellido",
            "cedula_identidad=:cedula"
        ];

        $params = [
            ":nombre" => $this->nombre,
            ":apellido" => $this->apellido,
            ":cedula" => $this->cedula_identidad,
            ":id" => $this->id_estudiante
        ];

        if (!empty($this->id_grado)) {
            $campos[] = "id_grado=:id_grado";
            $params[":id_grado"] = $this->id_grado;
        }

        $query = "UPDATE estudiantes
                  SET " . implode(", ", $campos) . "
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function desactivar() {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=0
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function restaurar() {
        if (empty($this->id_estudiante)) return false;

        $query = "UPDATE estudiantes
                  SET activo=1
                  WHERE id_estudiante=:id";

        $stmt = $this->conn->prepare($query);
        return $stmt->execute([":id" => $this->id_estudiante]);
    }

    public function contarDependencias() {
        $query = "SELECT
                    (SELECT COUNT(*) FROM asistencias_estudiantes WHERE id_estudiante=:id1) +
                    (SELECT COUNT(*) FROM solicitudes_huella WHERE id_estudiante=:id2)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":id1" => $this->id_estudiante,
            ":id2" => $this->id_estudiante
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function eliminar() {
        if (empty($this->id_estudiante)) return false;

        $stmt = $this->conn->prepare("DELETE FROM estudiantes WHERE id_estudiante=:id");
        $stmt->execute([":id" => $this->id_estudiante]);

        return $stmt->rowCount() > 0;
    }
}
?>