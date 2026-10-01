<?php
declare(strict_types=1);

/** Returns only an active, positive database identity matching the login session. */
function validarIdentidadSesion(PDO $db, array $session): ?array
{
    $raw = $session['id_usuario'] ?? null;
    if (!is_int($raw) && !is_string($raw)) return null;
    $id = filter_var($raw, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
    $role = $session['rol'] ?? null;
    if ($id === false || !is_string($role) || $role === '') return null;
    $query = $db->prepare('SELECT id_usuario, rol, activo FROM usuarios WHERE id_usuario=? LIMIT 1');
    $query->execute([$id]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row || (int)$row['activo'] !== 1 || $row['rol'] !== $role) return null;
    return ['id_usuario'=>(int)$row['id_usuario'], 'rol'=>(string)$row['rol']];
}
