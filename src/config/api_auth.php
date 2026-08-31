<?php
require_once __DIR__ . '/db.php';

function iniciarSesionSegura(): void
{
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function responderJson(array $payload, int $codigo = 200): never
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requerirMetodo(array $metodos): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', $metodos, true)) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'Metodo no permitido.'], 405);
    }
}

function usuarioActual(array $roles = []): array
{
    iniciarSesionSegura();
    if (!isset($_SESSION['id_usuario']) || empty($_SESSION['rol'])) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'Sesion no valida.'], 401);
    }
    $usuario = ['id_usuario' => (int) $_SESSION['id_usuario'], 'rol' => (string) $_SESSION['rol']];
    if ($roles && !in_array($usuario['rol'], $roles, true)) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'No tiene permisos para esta operacion.'], 403);
    }
    return $usuario;
}

function esAdministradorPedagogico(array $usuario): bool
{
    return in_array($usuario['rol'], ['SuperAdmin', 'Administracion', 'Coordinador'], true);
}

function profesorDeUsuario(PDO $db, array $usuario): ?int
{
    $stmt = $db->prepare('SELECT id_profesor FROM profesores WHERE id_usuario = :id_usuario AND activo = 1 LIMIT 1');
    $stmt->execute([':id_usuario' => $usuario['id_usuario']]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}

function asegurarAccesoAsignacion(PDO $db, array $usuario, int $idAsignacion, bool $escritura = false): array
{
    $stmt = $db->prepare("SELECT ad.*, p.nombre AS profesor_nombre, p.apellido AS profesor_apellido,
                                m.nombre AS materia, g.nombre AS grado, g.id_aula, a.nombre AS aula
                         FROM asignacion_docente ad
                         INNER JOIN profesores p ON p.id_profesor = ad.id_profesor
                         INNER JOIN materias m ON m.id_materia = ad.id_materia
                         INNER JOIN grados g ON g.id_grado = ad.id_grado
                         INNER JOIN aulas a ON a.id_aula = g.id_aula
                         WHERE ad.id_asignacion = :id LIMIT 1");
    $stmt->execute([':id' => $idAsignacion]);
    $asignacion = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$asignacion) responderJson(['success' => false, 'status' => 'error', 'message' => 'La asignacion no existe.'], 404);
    if ($escritura && !(int) $asignacion['activo']) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'La asignacion esta inactiva.'], 409);
    }
    if ($usuario['rol'] === 'Profesor') {
        $idProfesor = profesorDeUsuario($db, $usuario);
        if (!$idProfesor) {
            responderJson(['success' => false, 'status' => 'error', 'message' => 'La cuenta no esta vinculada a un profesor activo.'], 403);
        }
        if ($idProfesor !== (int) $asignacion['id_profesor']) {
            responderJson(['success' => false, 'status' => 'error', 'message' => 'Solo puede acceder a sus propias asignaciones.'], 403);
        }
    } elseif (!esAdministradorPedagogico($usuario)) {
        responderJson(['success' => false, 'status' => 'error', 'message' => 'No tiene permisos para esta asignacion.'], 403);
    }
    return $asignacion;
}

function entradaJson(): array
{
    $tipo = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (str_contains($tipo, 'application/json')) {
        $datos = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($datos)) responderJson(['success' => false, 'status' => 'error', 'message' => 'JSON no valido.'], 400);
        return $datos;
    }
    return $_POST;
}

function enteroPositivo(mixed $valor, string $nombre): int
{
    $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!$id) responderJson(['success' => false, 'status' => 'error', 'message' => "{$nombre} no valido."], 422);
    return (int) $id;
}

function textoRequerido(mixed $valor, string $nombre, int $maximo = 10000): string
{
    $texto = trim((string) $valor);
    if ($texto === '' || mb_strlen($texto) > $maximo) {
        responderJson(['success' => false, 'status' => 'error', 'message' => "{$nombre} es obligatorio o excede el limite permitido."], 422);
    }
    return $texto;
}
?>
