<?php
declare(strict_types=1);

/**
 * Helper para garantizar la coherencia transaccional de cuentas de usuario
 * y los perfiles de Profesor, Estudiante y Tutor en SISCO-EDU.
 */
class IdentidadHelper {

    /**
     * Asegura que exista una cuenta de usuario en 'usuarios' para una cédula dada.
     * Si ya existe, retorna su id_usuario.
     * Si no existe, la crea con credenciales por defecto (usuario = CI, pass = CI)
     * y retorna el nuevo id_usuario.
     */
    public static function asegurarUsuario(
        PDO $db,
        string $rol,
        string $cedula,
        string $nombre,
        string $apellido,
        ?string $email = null,
        ?string $celular = null
    ): int {
        $cedulaLimpia = trim($cedula);
        $rolLimpio = trim($rol);
        $nombreLimpio = trim($nombre);
        $apellidoLimpio = trim($apellido);

        if (empty($cedulaLimpia)) {
            return 0;
        }

        // 1. Buscar si ya existe por usuario = cedula
        $stmtBusq = $db->prepare("SELECT id_usuario, rol FROM usuarios WHERE usuario = :cedula LIMIT 1");
        $stmtBusq->execute([':cedula' => $cedulaLimpia]);
        $existente = $stmtBusq->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            return (int)$existente['id_usuario'];
        }

        // 2. Si no existe, preparar credenciales por defecto
        $subdominio = strtolower($rolLimpio);
        $emailFinal = !empty($email) ? trim($email) : ($cedulaLimpia . '@' . $subdominio . '.sisco.edu');
        $celularFinal = !empty($celular) ? trim($celular) : '0981000000';
        $passwordHash = password_hash($cedulaLimpia, PASSWORD_DEFAULT);

        // Si ya hay un usuario con ese email exacto, desambiguar
        $stmtCheckEmail = $db->prepare("SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1");
        $stmtCheckEmail->execute([':email' => $emailFinal]);
        if ($stmtCheckEmail->fetch()) {
            $emailFinal = $cedulaLimpia . '.' . strtolower($rolLimpio) . '.' . random_int(100, 999) . '@sisco.edu';
        }

        $stmtInsert = $db->prepare("
            INSERT INTO usuarios (nombre, apellido, usuario, email, celular, password, rol, activo)
            VALUES (:nombre, :apellido, :usuario, :email, :celular, :password, :rol, 1)
            RETURNING id_usuario
        ");
        $stmtInsert->execute([
            ':nombre' => $nombreLimpio,
            ':apellido' => $apellidoLimpio,
            ':usuario' => $cedulaLimpia,
            ':email' => $emailFinal,
            ':celular' => $celularFinal,
            ':password' => $passwordHash,
            ':rol' => $rolLimpio
        ]);

        $idUsuario = (int)$stmtInsert->fetchColumn();
        return $idUsuario;
    }

    /**
     * Sincroniza y vincula masivamente todos los registros huérfanos
     * en profesores, estudiantes y tutores que no tienen id_usuario asignado.
     */
    public static function sincronizarTodo(PDO $db): array {
        $stats = [
            'profesores_vinculados' => 0,
            'estudiantes_vinculados' => 0,
            'tutores_vinculados' => 0,
        ];

        // 1. Profesores huérfanos
        $stmtP = $db->query("SELECT id_profesor, nombre, apellido, cedula_identidad FROM profesores WHERE id_usuario IS NULL");
        $profesores = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        $stmtUpdateP = $db->prepare("UPDATE profesores SET id_usuario = :id_usuario WHERE id_profesor = :id_profesor");

        foreach ($profesores as $p) {
            $ci = trim((string)$p['cedula_identidad']);
            if (empty($ci)) continue;
            try {
                $idUser = self::asegurarUsuario($db, 'Profesor', $ci, (string)$p['nombre'], (string)$p['apellido']);
                if ($idUser > 0) {
                    $stmtUpdateP->execute([':id_usuario' => $idUser, ':id_profesor' => (int)$p['id_profesor']]);
                    $stats['profesores_vinculados']++;
                }
            } catch (Throwable $e) {
                error_log("Error vinculando profesor {$p['id_profesor']}: " . $e->getMessage());
            }
        }

        // 2. Estudiantes huérfanos
        $stmtE = $db->query("SELECT id_estudiante, nombre, apellido, cedula_identidad FROM estudiantes WHERE id_usuario IS NULL");
        $estudiantes = $stmtE->fetchAll(PDO::FETCH_ASSOC);
        $stmtUpdateE = $db->prepare("UPDATE estudiantes SET id_usuario = :id_usuario WHERE id_estudiante = :id_estudiante");

        foreach ($estudiantes as $e) {
            $ci = trim((string)$e['cedula_identidad']);
            if (empty($ci)) continue;
            try {
                $idUser = self::asegurarUsuario($db, 'Estudiante', $ci, (string)$e['nombre'], (string)$e['apellido']);
                if ($idUser > 0) {
                    $stmtUpdateE->execute([':id_usuario' => $idUser, ':id_estudiante' => (int)$e['id_estudiante']]);
                    $stats['estudiantes_vinculados']++;
                }
            } catch (Throwable $e) {
                error_log("Error vinculando estudiante {$e['id_estudiante']}: " . $e->getMessage());
            }
        }

        // 3. Tutores huérfanos
        $stmtT = $db->query("SELECT id_tutor, nombre, apellido, cedula_identidad, email, celular FROM tutores WHERE id_usuario IS NULL");
        $tutores = $stmtT->fetchAll(PDO::FETCH_ASSOC);
        $stmtUpdateT = $db->prepare("UPDATE tutores SET id_usuario = :id_usuario WHERE id_tutor = :id_tutor");

        foreach ($tutores as $t) {
            $ci = trim((string)$t['cedula_identidad']);
            if (empty($ci)) continue;
            try {
                $idUser = self::asegurarUsuario($db, 'Tutor', $ci, (string)$t['nombre'], (string)$t['apellido'], $t['email'] ?? null, $t['celular'] ?? null);
                if ($idUser > 0) {
                    $stmtUpdateT->execute([':id_usuario' => $idUser, ':id_tutor' => (int)$t['id_tutor']]);
                    $stats['tutores_vinculados']++;
                }
            } catch (Throwable $e) {
                error_log("Error vinculando tutor {$t['id_tutor']}: " . $e->getMessage());
            }
        }

        return $stats;
    }
}
