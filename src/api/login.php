<?php
session_start();

require_once '../config/db.php';

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

            if (password_verify($password, $row['password'])) {

                // ✅ CREAR SESIÓN
                $_SESSION['usuario'] = $row['usuario'];
                $_SESSION['rol'] = $row['rol'];
                $_SESSION['id_usuario'] = $row['id_usuario'];

                // 🔥 REDIRECCIÓN AL DASHBOARD
                header("Location: ../views/dashboard.php");
                exit();

            } else {
                echo "❌ Contraseña incorrecta";
            }

        } else {
            echo "❌ Usuario no encontrado";
        }

    } else {
        echo "❌ Datos incompletos";
    }

} else {
    echo "⛔ Método no permitido";
}
?>