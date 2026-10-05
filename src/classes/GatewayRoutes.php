<?php
final class GatewayRoutes
{
    public static function load(PDO $db): array
    {
        $rows=$db->query("SELECT a.id_aula,a.codigo AS room_id,n.node_id,n.lora_id FROM nodos_esp32 n JOIN aulas a ON a.codigo=n.room_id AND a.activo=1 WHERE n.tipo='AULA' AND n.activo=1 ORDER BY a.id_aula")->fetchAll(PDO::FETCH_ASSOC);
        $ids=[]; $aulas=[];
        if (count($rows)>32) throw new RuntimeException('Más de 32 rutas: capacidad firmware excedida');
        foreach ($rows as $r) {
            $id=(int)$r['lora_id']; $aula=(int)$r['id_aula'];
            if ($id<1 || $id>65535 || isset($ids[$id]) || isset($aulas[$aula])) throw new RuntimeException('Rutas incompletas o ambiguas');
            $ids[$id]=true; $aulas[$aula]=true;
        }
        return $rows;
    }
}
