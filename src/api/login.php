<?php
session_start();

require_once '../config/db.php';

header("Content-Type: application/json; charset=UTF-8");

$db = (new Database())->getConnection();

// 🔥 IMPORTANTE: aceptar POST correctamente
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 👇 ESTO ES CLAVE (mejor que $_POST para APIs)
    $data = $_POST;

    // fallback por si viene JSON
    if(empty($data)){
        $data = json_decode(file_get_contents("php://input"), true);
    }

    $usuario = $data['usuario'] ?? null;
    $password = $data['password'] ?? null;

    if (!empty($usuario) && !empty($password)) {

        $query = "SELECT * FROM usuarios WHERE usuario = :usuario LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":usuario", $usuario);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {

            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($password, $row['password'])) {

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
    echo json_encode([
        "message" => "Método no permitido",
        "metodo_recibido" => $_SERVER['REQUEST_METHOD'] // 👈 DEBUG
    ]);
}