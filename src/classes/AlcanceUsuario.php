<?php
declare(strict_types=1);

/**
 * Clase AlcanceUsuario
 * Gestiona la segmentación granular de turnos, niveles, bachilleratos y cursos
 * para Administradores y Coordinadores dentro de SISCO-EDU.
 */
class AlcanceUsuario {
    /**
     * Retorna los IDs de grados a los que el usuario tiene acceso permitido.
     * Retorna `null` si el usuario tiene acceso irrestricto (SuperAdmin o Administrador global).
     * Retorna array de enteros `[1, 2, ...]` si tiene alcance restringido.
     */
    public static function obtenerGradosPermitidos(PDO $db, int $idUsuario, ?string $rol): ?array {
        if ($rol === 'SuperAdmin') {
            return null; // SuperAdmin siempre tiene acceso total
        }

        $stmt = $db->prepare("SELECT id_grado FROM public.coordinador_grados WHERE id_usuario = :id_usuario AND activo = 1");
        $stmt->execute([':id_usuario' => $idUsuario]);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($ids)) {
            return array_map('intval', $ids);
        }

        // Si es Coordinador y no tiene asignación, su alcance es vacío (debe ser asignado por el SuperAdmin)
        if ($rol === 'Coordinador') {
            return [];
        }

        // Si es Administrador y aún no tiene registros en coordinador_grados, conserva acceso global
        return null;
    }

    /**
     * Retorna la estructura institucional jerárquica con el estado de asignación para un usuario.
     */
    public static function obtenerEstructuraAlcance(PDO $db, int $idUsuario): array {
        // Datos del usuario
        $stmtU = $db->prepare("SELECT id_usuario, nombre, apellido, usuario, email, rol FROM public.usuarios WHERE id_usuario = :id");
        $stmtU->execute([':id' => $idUsuario]);
        $usuario = $stmtU->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            throw new InvalidArgumentException("Usuario no encontrado.");
        }

        // Grados asignados actualmente
        $stmtA = $db->prepare("SELECT id_grado FROM public.coordinador_grados WHERE id_usuario = :id AND activo = 1");
        $stmtA->execute([':id' => $idUsuario]);
        $idsAsignados = array_map('intval', $stmtA->fetchAll(PDO::FETCH_COLUMN));

        // Catálogo completo de grados con turno, nivel, bachillerato y aula
        $sqlGrados = "
            SELECT 
                g.id_grado,
                g.nombre AS grado_nombre,
                g.seccion,
                g.id_turno,
                COALESCE(t.nombre, g.turno, 'Sin turno') AS turno_nombre,
                g.id_nivel,
                COALESCE(n.nombre, 'Sin nivel') AS nivel_nombre,
                g.id_bachillerato,
                COALESCE(b.nombre, b.codigo, 'Educación General') AS bachillerato_nombre,
                g.id_aula,
                COALESCE(a.nombre, 'Sin aula') AS aula_nombre
            FROM public.grados g
            LEFT JOIN public.turnos t ON g.id_turno = t.id_turno
            LEFT JOIN public.niveles_academicos n ON g.id_nivel = n.id_nivel
            LEFT JOIN public.bachilleratos b ON g.id_bachillerato = b.id_bachillerato
            LEFT JOIN public.aulas a ON g.id_aula = a.id_aula
            WHERE g.activo = 1
            ORDER BY t.nombre ASC, n.nombre ASC, g.nombre ASC, g.seccion ASC
        ";
        $grados = $db->query($sqlGrados)->fetchAll(PDO::FETCH_ASSOC);

        // Agrupación jerárquica para filtros en bloque
        $turnosMap = [];
        $nivelesMap = [];
        $bachilleratosMap = [];

        foreach ($grados as &$g) {
            $g['id_grado'] = (int)$g['id_grado'];
            $g['id_turno'] = $g['id_turno'] ? (int)$g['id_turno'] : null;
            $g['id_nivel'] = $g['id_nivel'] ? (int)$g['id_nivel'] : null;
            $g['id_bachillerato'] = $g['id_bachillerato'] ? (int)$g['id_bachillerato'] : null;
            $g['asignado'] = in_array($g['id_grado'], $idsAsignados, true);

            // Turnos
            $tNombre = trim((string)$g['turno_nombre']);
            if (!isset($turnosMap[$tNombre])) {
                $turnosMap[$tNombre] = ['nombre' => $tNombre, 'id_turno' => $g['id_turno'], 'grados' => []];
            }
            $turnosMap[$tNombre]['grados'][] = $g['id_grado'];

            // Niveles
            $nNombre = trim((string)$g['nivel_nombre']);
            if (!isset($nivelesMap[$nNombre])) {
                $nivelesMap[$nNombre] = ['nombre' => $nNombre, 'id_nivel' => $g['id_nivel'], 'grados' => []];
            }
            $nivelesMap[$nNombre]['grados'][] = $g['id_grado'];

            // Bachilleratos
            $bNombre = trim((string)$g['bachillerato_nombre']);
            if (!isset($bachilleratosMap[$bNombre])) {
                $bachilleratosMap[$bNombre] = ['nombre' => $bNombre, 'id_bachillerato' => $g['id_bachillerato'], 'grados' => []];
            }
            $bachilleratosMap[$bNombre]['grados'][] = $g['id_grado'];
        }
        unset($g);

        return [
            'usuario' => $usuario,
            'ids_asignados' => $idsAsignados,
            'total_asignados' => count($idsAsignados),
            'grados' => $grados,
            'turnos' => array_values($turnosMap),
            'niveles' => array_values($nivelesMap),
            'bachilleratos' => array_values($bachilleratosMap)
        ];
    }

    /**
     * Guarda atómicamente el alcance de grados para un usuario.
     */
    public static function guardarAlcance(PDO $db, int $idUsuario, array $idsGrados): array {
        $db->beginTransaction();
        try {
            // Desactivar todas las asignaciones previas del usuario
            $stmtOff = $db->prepare("UPDATE public.coordinador_grados SET activo = 0 WHERE id_usuario = :id");
            $stmtOff->execute([':id' => $idUsuario]);

            $totalGuardados = 0;
            if (!empty($idsGrados)) {
                $stmtInsert = $db->prepare("
                    INSERT INTO public.coordinador_grados (id_usuario, id_grado, activo, fecha_asignacion)
                    VALUES (:id_usuario, :id_grado, 1, NOW())
                    ON CONFLICT (id_usuario, id_grado)
                    DO UPDATE SET activo = 1, fecha_asignacion = NOW()
                ");

                foreach ($idsGrados as $idGrado) {
                    $idGrado = (int)$idGrado;
                    if ($idGrado > 0) {
                        $stmtInsert->execute([
                            ':id_usuario' => $idUsuario,
                            ':id_grado' => $idGrado
                        ]);
                        $totalGuardados++;
                    }
                }
            }

            $db->commit();
            return [
                'ok' => true,
                'total_asignados' => $totalGuardados
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("[AlcanceUsuario::guardarAlcance] Error: " . $e->getMessage());
            throw $e;
        }
    }
}
