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

            <div>
                <label class="block text-sm font-medium mb-1" style="color: var(--color-text);">
                    Usuario
                </label>
                <input 
                    type="text" 
                    name="usuario" 
                    required
                    class="w-full px-4 py-3 border rounded-xl outline-none focus:ring-2"
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
                    class="w-full px-4 py-3 border rounded-xl outline-none focus:ring-2"
                    placeholder="••••••••"
                >
            </div>

            <button 
                type="submit"
                class="w-full py-3 rounded-xl text-white font-semibold transition hover:opacity-90"
                style="background: var(--color-primary);"
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