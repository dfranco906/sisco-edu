<?php
declare(strict_types=1);

/** Explicit maintenance operation. Never invoked automatically by login. */
final class ZeroUserIdentityRepair
{
    public function __construct(private readonly PDO $db) {}

    public function run(string $username, int $newId, bool $apply = false): array
    {
        if ($newId < 1 || $this->db->inTransaction()) throw new RuntimeException('Invalid repair context.');
        $this->db->beginTransaction();
        try {
            $query=$this->db->prepare('SELECT id_usuario,rol,activo FROM usuarios WHERE usuario=? FOR UPDATE');
            $query->execute([$username]); $user=$query->fetch(PDO::FETCH_ASSOC);
            if (!$user || $user['rol']!=='SuperAdmin' || (int)$user['activo']!==1) throw new RuntimeException('Expected active administrator not found.');
            if ((int)$user['id_usuario']===$newId) {
                $this->db->rollBack();
                return ['status'=>'already_repaired','new_id'=>$newId];
            }
            if ((int)$user['id_usuario']!==0) throw new RuntimeException('Only the explicitly selected zero identity can be repaired.');
            $query=$this->db->prepare('SELECT id_usuario FROM usuarios WHERE id_usuario=? FOR UPDATE');
            $query->execute([$newId]);
            if ($query->fetchColumn()!==false) throw new RuntimeException('Target identity is occupied.');
            $references=$this->db->query("SELECT k.TABLE_NAME,k.COLUMN_NAME,r.UPDATE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_NAME='usuarios' AND k.REFERENCED_COLUMN_NAME='id_usuario'")->fetchAll(PDO::FETCH_ASSOC);
            $columns=['usuarios'=>['id_usuario']]; $counts=[];
            foreach ($references as $reference) {
                $table=$this->identifier($reference['TABLE_NAME']); $column=$this->identifier($reference['COLUMN_NAME']);
                if ($reference['UPDATE_RULE']!=='CASCADE') throw new RuntimeException('A reference does not permit transactional cascade.');
                $columns[$table][]=$column;
                $counts[$table.'.'.$column]=(int)$this->db->query("SELECT COUNT(*) FROM `$table` WHERE `$column`=0")->fetchColumn();
            }
            $tables=$this->db->query("SELECT TABLE_NAME,ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($tables as $table) {
                if (isset($columns[$table['TABLE_NAME']]) && $table['ENGINE']!=='InnoDB') throw new RuntimeException('Identity and references require transactional tables.');
            }
            $report=['status'=>$apply?'repaired':'dry_run','old_id'=>0,'new_id'=>$newId,'references'=>$counts];
            if (!$apply) { $this->db->rollBack(); return $report; }
            $before=$this->snapshot($tables, $columns, $newId);
            $query=$this->db->prepare("UPDATE usuarios SET id_usuario=? WHERE id_usuario=0 AND usuario=? AND rol='SuperAdmin' AND activo=1");
            $query->execute([$newId,$username]);
            if ($query->rowCount()!==1) throw new RuntimeException('Administrator identity was not updated exactly once.');
            $after=$this->snapshot($tables, $columns, $newId);
            if ($before!==$after) throw new RuntimeException('Unexpected data change detected; repair rolled back.');
            foreach ($references as $reference) {
                $table=$this->identifier($reference['TABLE_NAME']); $column=$this->identifier($reference['COLUMN_NAME']);
                if ((int)$this->db->query("SELECT COUNT(*) FROM `$table` WHERE `$column`=0")->fetchColumn()!==0) throw new RuntimeException('An old identity reference remains.');
            }
            $this->db->commit();
            return $report+['only_identity_references_changed'=>true,'rows_inserted'=>0];
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $error;
        }
    }

    /** Compare every row, normalizing only the approved identity/reference columns. */
    private function snapshot(array $tables, array $columns, int $newId): array
    {
        $result=[];
        foreach ($tables as $tableInfo) {
            $table=$this->identifier($tableInfo['TABLE_NAME']); $hashes=[];
            foreach ($this->db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC) as $row) {
                foreach ($columns[$table]??[] as $column) {
                    if ($row[$column]!==null && (int)$row[$column]===$newId) $row[$column]=0;
                }
                $hashes[]=hash('sha256',serialize($row));
            }
            sort($hashes,SORT_STRING);
            $result[$table]=hash('sha256',implode('', $hashes));
        }
        ksort($result,SORT_STRING);
        return $result;
    }

    private function identifier(string $value): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D',$value)) throw new RuntimeException('Unsupported schema identifier.');
        return $value;
    }
}
