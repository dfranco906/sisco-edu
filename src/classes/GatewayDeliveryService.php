<?php
require_once __DIR__.'/GatewayAttendanceService.php';

/** Recibo y efectos oficiales se confirman juntos; reintentos devuelven el mismo resultado. */
final class GatewayDeliveryService
{
    public function __construct(private PDO $db) {}

    public function deliver(string $source, string $tipo, string $ci, int $aula, string $estado): array
    {
        if (preg_match('/\A[a-zA-Z0-9_-]{16,80}\z/D', $source) !== 1 || $estado !== 'PRESENTE') {
            return ['ok'=>false,'status'=>'datos_invalidos','source_event_id'=>$source];
        }
        $hash = hash('sha256', json_encode([$tipo,$ci,$aula,$estado], JSON_THROW_ON_ERROR));
        $this->db->beginTransaction();
        try {
            $s=$this->db->prepare('INSERT INTO gateway_delivery_receipts (source_event_id,payload_hash) VALUES (?,?) ON DUPLICATE KEY UPDATE source_event_id=VALUES(source_event_id)');
            $s->execute([$source,$hash]);
            $s=$this->db->prepare('SELECT payload_hash,response_json,received_at FROM gateway_delivery_receipts WHERE source_event_id=? FOR UPDATE');
            $s->execute([$source]); $receipt=$s->fetch(PDO::FETCH_ASSOC);
            if (!hash_equals($receipt['payload_hash'],$hash)) {
                $this->db->rollBack();
                return ['ok'=>false,'status'=>'source_event_conflict','source_event_id'=>$source];
            }
            if ($receipt['response_json'] !== null) {
                $result=json_decode($receipt['response_json'],true,512,JSON_THROW_ON_ERROR);
                $result['delivery_idempotente']=true;
                $this->db->commit(); return $result;
            }
            $service=new GatewayAttendanceService($this->db);
            $moment=new DateTimeImmutable($receipt['received_at'],new DateTimeZone(SISCO_TIMEZONE));
            $result=$service->registrar($tipo,$ci,$aula,$moment);
            $result['source_event_id']=$source;
            $result['delivery_idempotente']=false;
            if (!empty($result['data']['id_evento'])) {
                $this->db->prepare('UPDATE eventos_asistencia SET source_event_id=? WHERE id_evento=?')->execute([$source,$result['data']['id_evento']]);
            }
            $this->db->prepare('UPDATE gateway_delivery_receipts SET response_json=? WHERE source_event_id=?')->execute([json_encode($result,JSON_THROW_ON_ERROR),$source]);
            $this->db->commit(); return $result;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }
}
