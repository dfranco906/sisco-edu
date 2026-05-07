<!DOCTYPE html>
<html>
<head>
    <title>Login SISCO-EDU</title>
</head>
<body>

<h2>Iniciar sesión</h2>

<form action="../../../src/api/login.php" method="POST">
    Usuario:<br>
    <input type="text" name="usuario" required><br><br>

    Contraseña:<br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Ingresar</button>
</form>

</body>
</html>