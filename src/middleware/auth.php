<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si está autenticado (excepto en scripts de login/logout)
$scriptActual = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$esRutaLogin = str_contains($scriptActual, 'login.php') || str_contains($scriptActual, 'logout.php');

if (!$esRutaLogin && (!isset($_SESSION['usuario']) || empty($_SESSION['id_usuario']))) {
    $isApi = str_contains($scriptActual, '/src/api/')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isApi) {
        http_response_code(401);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['status' => 'error', 'message' => 'No autenticado']);
        exit();
    }

    header("Location: " . base_url('mvc/views/auth/login.php'));
    exit();
}

/**
 * Retorna la ruta del dashboard principal según el rol del usuario
 */
function obtenerRutaDashboardPorRol(?string $rol): string {
    return match ($rol) {
        'SuperAdmin', 'Administrador', 'Administracion' => 'mvc/views/dashboard.php',
        'Coordinador' => 'mvc/views/coordinacion/index.php',
        'Profesor' => 'mvc/views/profesores/mi_panel.php',
        'Estudiante' => 'mvc/views/estudiantes/mi_panel.php',
        'Tutor' => 'mvc/views/tutores/mi_panel.php',
        default => 'mvc/views/dashboard.php',
    };
}

/**
 * Valida si el rol en sesión tiene permisos para acceder al recurso.
 * SuperAdmin siempre tiene acceso concedido a cualquier recurso.
 */
function verificarRol(array $rolesPermitidos): void {
    $rolActual = $_SESSION['rol'] ?? '';

    if ($rolActual === 'SuperAdmin') {
        return;
    }

    if (!in_array($rolActual, $rolesPermitidos, true)) {
        $isApi = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/src/api/')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

        if ($isApi) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: permisos insuficientes']);
            exit();
        }

        // Redirigir al dashboard propio de su rol
        $destino = obtenerRutaDashboardPorRol($rolActual);
        header("Location: " . base_url($destino) . "?error=acceso_denegado");
        exit();
    }
}

/**
 * Retorna los datos básicos del usuario autenticado
 */
function obtenerUsuarioActual(): array {
    return [
        'id_usuario' => (int)($_SESSION['id_usuario'] ?? 0),
        'usuario' => $_SESSION['usuario'] ?? '',
        'rol' => $_SESSION['rol'] ?? '',
        'nombre' => $_SESSION['nombre'] ?? '',
        'apellido' => $_SESSION['apellido'] ?? '',
    ];
}