<?php
class EventoAsistencia {
    private $conn;
    private $table_name = "eventos_asistencia";
    public $id_evento, $user_id_global, $id_aula, $estado, $timestamp_evento, $origen_node_id, $sincronizado;
    public function __construct($db) { $this->conn = $db; }
    public function crear() {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table_name} (user_id_global,id_aula,estado,timestamp_evento,origen_node_id,sincronizado) VALUES (:user_id_global,:id_aula,:estado,:timestamp_evento,:origen_node_id,:sincronizado)");
        return $stmt->execute([':user_id_global'=>$this->user_id_global, ':id_aula'=>$this->id_aula, ':estado'=>$this->estado, ':timestamp_evento'=>$this->timestamp_evento, ':origen_node_id'=>$this->origen_node_id, ':sincronizado'=>$this->sincronizado]);
    }
    public function leer() { $stmt=$this->conn->prepare("SELECT * FROM {$this->table_name} WHERE activo=1 ORDER BY id_evento DESC"); $stmt->execute(); return $stmt; }
    public function actualizar() {
        $stmt=$this->conn->prepare("UPDATE {$this->table_name} SET user_id_global=:user_id_global,id_aula=:id_aula,estado=:estado,timestamp_evento=:timestamp_evento,origen_node_id=:origen_node_id,sincronizado=:sincronizado WHERE id_evento=:id");
        return $stmt->execute([':user_id_global'=>$this->user_id_global, ':id_aula'=>$this->id_aula, ':estado'=>$this->estado, ':timestamp_evento'=>$this->timestamp_evento, ':origen_node_id'=>$this->origen_node_id, ':sincronizado'=>$this->sincronizado, ':id'=>$this->id_evento]);
    }
    public function desactivar() { $stmt=$this->conn->prepare("UPDATE {$this->table_name} SET activo=0 WHERE id_evento=:id"); return $stmt->execute([':id'=>$this->id_evento]); }
}