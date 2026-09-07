<?php

class DistribucionHuellaProfesor
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerProfesorActivo(string $userIdGlobal): ?array
    {
        $stmt = $this->db->prepare("SELECT id_profesor, nombre, apellido, cedula_identidad, user_id_global
            FROM profesores
            WHERE user_id_global=:user_id AND activo=1
            LIMIT 1");
        $stmt->execute([':user_id' => trim($userIdGlobal)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function ultimaHuellaActiva(int $idProfesor): ?array
    {
        $stmt = $this->db->prepare("SELECT id_huella, user_id_global, fecha_creacion
            FROM huellas_templates
            WHERE activo=1 AND (
                id_profesor=:profesor
                OR (
                    id_profesor IS NULL
                    AND user_id_global=(SELECT user_id_global FROM profesores WHERE id_profesor=:profesor_uid LIMIT 1)
                )
            )
            ORDER BY id_huella DESC
            LIMIT 1");
        $stmt->execute([':profesor' => $idProfesor, ':profesor_uid' => $idProfesor]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function aulasAsignadas(int $idProfesor, ?int $idHuella = null): array
    {
        $stmt = $this->db->prepare("SELECT a.id_aula, a.nombre, a.codigo, a.ubicacion,
                   GROUP_CONCAT(DISTINCT g.nombre ORDER BY g.nombre SEPARATOR ', ') AS grados,
                   EXISTS(
                       SELECT 1 FROM nodos_esp32 n
                       WHERE n.tipo='AULA' AND n.activo=1
                         AND n.room_id IN (a.codigo, g.room_id)
                   ) AS nodo_registrado,
                   (
                       SELECT s.estado
                       FROM sync_biometrica s
                       WHERE s.id_huella=:huella AND s.id_aula=a.id_aula
                       ORDER BY s.id_sync DESC
                       LIMIT 1
                   ) AS estado_sync
            FROM horarios h
            INNER JOIN asignacion_docente ad
                ON ad.id_asignacion=h.id_asignacion AND ad.activo=1
            INNER JOIN grados g
                ON g.id_grado=h.id_grado AND g.activo=1
            INNER JOIN aulas a
                ON a.id_aula=h.id_aula AND a.activo=1
            WHERE h.activo=1 AND ad.id_profesor=:profesor
            GROUP BY a.id_aula, a.nombre, a.codigo, a.ubicacion
            ORDER BY a.nombre");
        $stmt->execute([':huella' => $idHuella, ':profesor' => $idProfesor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function normalizarIdsAula($valores): array
    {
        if (!is_array($valores)) $valores = [$valores];
        $ids = [];
        foreach ($valores as $valor) {
            $id = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id) $ids[] = (int) $id;
        }
        return array_values(array_unique($ids));
    }

    public function aulasValidas(int $idProfesor, array $idsAula): array
    {
        $idsAula = $this->normalizarIdsAula($idsAula);
        if (!$idsAula) return [];
        $marcadores = implode(',', array_fill(0, count($idsAula), '?'));
        $stmt = $this->db->prepare("SELECT DISTINCT a.id_aula, a.nombre, a.codigo
            FROM horarios h
            INNER JOIN asignacion_docente ad
                ON ad.id_asignacion=h.id_asignacion AND ad.activo=1
            INNER JOIN aulas a
                ON a.id_aula=h.id_aula AND a.activo=1
            WHERE h.activo=1 AND ad.id_profesor=? AND a.id_aula IN ({$marcadores})
            ORDER BY a.nombre");
        $stmt->execute(array_merge([$idProfesor], $idsAula));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function encolar(int $idHuella, int $idProfesor, array $idsAula): array
    {
        $idsAula = $this->normalizarIdsAula($idsAula);
        $aulas = $this->aulasValidas($idProfesor, $idsAula);
        $idsValidos = array_map('intval', array_column($aulas, 'id_aula'));
        if (!$idsAula || count($idsValidos) !== count($idsAula)) {
            throw new InvalidArgumentException('Seleccione solamente aulas activas incluidas en el horario del profesor.');
        }

        $buscar = $this->db->prepare("SELECT id_sync, estado
            FROM sync_biometrica
            WHERE id_huella=:huella AND id_aula=:aula
              AND estado IN ('PENDIENTE','ENVIADO','CONFIRMADO')
            ORDER BY id_sync DESC LIMIT 1");
        $insertar = $this->db->prepare("INSERT INTO sync_biometrica
            (id_huella,id_aula,estado,intentos)
            VALUES (:huella,:aula,'PENDIENTE',0)");
        $creadas = [];
        $omitidas = [];
        foreach ($idsValidos as $idAula) {
            $buscar->execute([':huella' => $idHuella, ':aula' => $idAula]);
            $existente = $buscar->fetch(PDO::FETCH_ASSOC);
            if ($existente) {
                $omitidas[] = [
                    'id_aula' => $idAula,
                    'id_sync' => (int) $existente['id_sync'],
                    'estado' => $existente['estado']
                ];
                continue;
            }
            $insertar->execute([':huella' => $idHuella, ':aula' => $idAula]);
            $creadas[] = [
                'id_aula' => $idAula,
                'id_sync' => (int) $this->db->lastInsertId(),
                'estado' => 'PENDIENTE'
            ];
        }
        return ['aulas' => $aulas, 'creadas' => $creadas, 'omitidas' => $omitidas];
    }

    public static function avisarGateway(): bool
    {
        if (!defined('GATEWAY_SYNC_URL') || !defined('GATEWAY_API_KEY') || !function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init(GATEWAY_SYNC_URL);
        if (!$ch) return false;
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-GATEWAY-KEY: ' . GATEWAY_API_KEY],
            CURLOPT_TIMEOUT => 3
        ]);
        $respuesta = curl_exec($ch);
        $codigo = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $respuesta !== false && $codigo >= 200 && $codigo < 300;
    }
}
