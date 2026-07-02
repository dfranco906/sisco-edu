<?php
$error = $_GET['error'] ?? null;

$mensaje = "";

if ($error === "campos") {
    $mensaje = "Completá todos los campos.";
} elseif ($error === "usuario") {
    $mensaje = "El usuario no existe.";
} elseif ($error === "password") {
    $mensaje = "La contraseña es incorrecta.";
} elseif ($error === "inactivo") {
    $mensaje = "Este usuario está inactivo.";
} elseif ($error === "logout") {
    $mensaje = "Sesión cerrada correctamente.";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | SISCO-EDU</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Variables globales -->
    <link rel="stylesheet" href="../../../public/css/theme.css">
</head>

<body class="min-h-screen flex items-center justify-center px-4" style="background: var(--color-bg);">

    <div class="w-full max-w-md bg-white shadow-xl rounded-2xl p-8">

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold" style="color: var(--color-text);">
                SISCO-EDU
            </h1>
            <p class="mt-2 text-sm" style="color: var(--color-muted);">
                Iniciá sesión para continuar
            </p>
        </div>

        <form action="../../../src/api/login.php" method="POST" class="space-y-5">
<?php if (!empty($mensaje)): ?>
    <div class="mb-4 p-3 rounded-xl text-sm bg-red-100 text-red-700">
        <?= $mensaje ?>
    </div>
<?php endif; ?>
            <div>
                <label class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                    Usuario
                </label>
                <input 
                    type="text" 
                    name="usuario" 
                    required
                    class="app-input w-full"
                    placeholder="Ej: admin1"
                >
            </div>

            <div>
                <label class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                    Contraseña
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required
                    class="app-input w-full"
                    placeholder="••••••••"
                >
            </div>

            <button 
                type="submit"
                class="btn btn-primary w-full"
            >
                Ingresar
            </button>

        </form>

        <p class="text-center text-xs mt-6" style="color: var(--color-muted);">
            Sistema de control de asistencia
        </p>

    </div>

</body>
</html>
