<?php
session_start();

require_once '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = $_POST['usuario'] ?? null;
    $password = $_POST['password'] ?? null;

    if (!empty($usuario) && !empty($password)) {

        $query = "SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // 🔐 VERIFICAR PASSWORD
            if (password_verify($password, $row['password'])) {

                // ✅ Crear sesión
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['id_usuario'] = $row['id_usuario'];

                echo json_encode([
                    "status" => "success",
                    "message" => "Login correcto",
                    "rol" => $row['rol']
                ]);

            } else {
                http_response_code(401);
                echo json_encode(["message" => "Contraseña incorrecta"]);
            }

        } else {
            http_response_code(404);
            echo json_encode(["message" => "Usuario no encontrado"]);
        }

    } else {
        http_response_code(400);
        echo json_encode(["message" => "Datos incompletos"]);
    }

} else {
    http_response_code(405);
    echo json_encode(["message" => "Método no permitido"]);
}
?>