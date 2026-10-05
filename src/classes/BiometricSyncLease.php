<?php
final class BiometricSyncLease
{
    public const TIMEOUT_SECONDS = 120;
    public const MAX_ATTEMPTS = 5;
    public static function claim(PDO $db, int $aula): ?array
    {
        $db->beginTransaction();
        try {
            $stale="(estado='PENDIENTE' OR (estado='ENVIADO' AND (fecha_actualizacion IS NULL OR fecha_actualizacion <= DATE_SUB(NOW(), INTERVAL 120 SECOND))))";
            $db->prepare("UPDATE sync_biometrica SET estado='ERROR', mensaje='MAX_INTENTOS_AGOTADOS', fecha_actualizacion=NOW() WHERE id_aula=? AND COALESCE(intentos,0)>=5 AND $stale")->execute([$aula]);
            $s=$db->prepare("SELECT id_sync,id_huella,id_aula FROM sync_biometrica WHERE id_aula=? AND COALESCE(intentos,0)<5 AND $stale ORDER BY id_sync LIMIT 1 FOR UPDATE");
            $s->execute([$aula]); $row=$s->fetch(PDO::FETCH_ASSOC);
            if ($row) $db->prepare("UPDATE sync_biometrica SET estado='ENVIADO', intentos=COALESCE(intentos,0)+1, fecha_actualizacion=NOW() WHERE id_sync=?")->execute([$row['id_sync']]);
            $db->commit(); return $row ?: null;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack(); throw $e;
        }
    }
}
