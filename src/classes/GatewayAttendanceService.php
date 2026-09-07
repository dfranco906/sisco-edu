<?php
require_once __DIR__ . '/EventoAsistencia.php';
require_once __DIR__ . '/ClaseDiaria.php';
require_once __DIR__ . '/AsistenciaProfesor.php';
require_once __DIR__ . '/AsistenciaEstudiante.php';

/** Procesa una marca ya autenticada del Gateway, sin conocer HTTP ni LoRa. */
final class GatewayAttendanceService
{
    public function __construct(private PDO $db) {}

    public function ahoraAutoritativo(): DateTimeImmutable
    {
        $valor=(string)$this->db->query("SELECT DATE_FORMAT(NOW(),'%Y-%m-%d %H:%i:%s')")->fetchColumn();
        $momento=DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$valor,new DateTimeZone(SISCO_TIMEZONE));
        if (!$momento || $momento->format('Y-m-d H:i:s')!==$valor) throw new RuntimeException('MySQL no devolvio una hora valida.');
        return $momento;
    }

    /** @return array<string,mixed> */
    public function registrar(string $tipo, string $ci, int $idAula, DateTimeImmutable $momento): array
    {
        $tipo = strtolower(trim($tipo));
        $ci = trim($ci);
        if (!in_array($tipo, ['profesor', 'estudiante'], true) || $ci === '' || $idAula < 1) {
            return ['ok'=>false, 'status'=>'datos_invalidos', 'message'=>'Datos de asistencia invalidos.'];
        }
        if (!$this->aulaActiva($idAula)) {
            return ['ok'=>false, 'status'=>'aula_invalida', 'message'=>'El aula no esta activa.'];
        }

        $persona = $this->persona($tipo, $ci);
        if (!$persona) return ['ok'=>false, 'status'=>'persona_no_encontrada', 'message'=>'Persona no encontrada o inactiva.'];

        // El evento fisico es una bitacora inmutable: se conserva aun cuando el
        // dominio no pueda asociarlo a una clase (por ejemplo, sin horario).
        $evento = new EventoAsistencia($this->db);
        $evento->user_id_global = $persona['user_id_global'];
        $evento->id_aula = $idAula;
        $evento->estado = 'PRESENTE';
        $evento->timestamp_evento = $momento->format('Y-m-d H:i:s');
        $evento->origen_node_id = 'GATEWAY_ESP32';
        $evento->sincronizado = 1;
        if (!$evento->crear()) throw new RuntimeException('No se pudo registrar el evento biometrico.');
        $idEvento = (int)$this->db->lastInsertId();

        return $tipo === 'profesor'
            ? $this->registrarProfesor($persona, $idAula, $momento, $idEvento)
            : $this->registrarEstudiante($persona, $idAula, $momento, $idEvento);
    }

    /** @param array<string,mixed> $persona @return array<string,mixed> */
    private function registrarProfesor(array $persona, int $idAula, DateTimeImmutable $momento, int $idEvento): array
    {
        if (empty($persona['id_huella'])) {
            return ['ok'=>false, 'status'=>'huella_no_configurada', 'message'=>'El profesor no tiene una huella activa.', 'data'=>['id_evento'=>$idEvento]];
        }
        $this->db->beginTransaction();
        try {
            $clases = new ClaseDiaria($this->db);
            $idClase = $clases->procesarMarcaProfesor((string)$persona['user_id_global'], $idAula, $momento->format('Y-m-d H:i:s'));
            if (!$idClase) {
                $this->db->rollBack();
                return ['ok'=>false, 'status'=>'sin_horario', 'message'=>'El profesor no tiene un horario valido para esta aula y hora.', 'data'=>['id_evento'=>$idEvento]];
            }
            $clase = $this->clase($idClase);
            $asistencia = (new AsistenciaProfesor($this->db))->registrarOReutilizar(
                (int)$persona['id_profesor'], (int)$persona['id_huella'], $clase['fecha'],
                $momento->format('H:i:s'), $clase['hora_inicio'], $clase['hora_fin']
            );
            $this->db->commit();
            return ['ok'=>true, 'status'=>'success', 'message'=>'Asistencia de profesor registrada.', 'data'=>[
                'id_evento'=>$idEvento, 'id_clase'=>$idClase, 'id_horario'=>(int)$clase['id_horario'],
                'id_asistencia_profesor'=>$asistencia['id_asistencia_profesor'], 'idempotente'=>!$asistencia['creada']
            ]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<string,mixed> $persona @return array<string,mixed> */
    private function registrarEstudiante(array $persona, int $idAula, DateTimeImmutable $momento, int $idEvento): array
    {
        if (empty($persona['id_huella'])) {
            return ['ok'=>false, 'status'=>'huella_no_configurada', 'message'=>'El estudiante no tiene una huella activa.', 'data'=>['id_evento'=>$idEvento]];
        }
        $this->db->beginTransaction();
        try {
            $clase = (new ClaseDiaria($this->db))->buscarClaseActivaParaAlumno(
                $idAula, $momento->format('Y-m-d H:i:s'), VENTANA_ASISTENCIA_ALUMNOS_MINUTOS
            );
            if (!$clase || (int)$clase['id_grado'] !== (int)$persona['id_grado']) {
                $this->db->rollBack();
                return ['ok'=>false, 'status'=>'sin_clase_activa', 'message'=>'No hay una clase activa para este estudiante.', 'data'=>['id_evento'=>$idEvento]];
            }
            $asistencia = (new AsistenciaEstudiante($this->db))->registrarOReutilizar(
                (int)$persona['id_estudiante'], (int)$persona['id_huella'], $clase['fecha'],
                $momento->format('H:i:s'), $clase['hora_inicio'], $clase['hora_fin']
            );
            $this->db->commit();
            return ['ok'=>true, 'status'=>'success', 'message'=>'Asistencia de estudiante registrada.', 'data'=>[
                'id_evento'=>$idEvento, 'id_clase'=>(int)$clase['id_clase'],
                'id_asistencia_estudiante'=>$asistencia['id_asistencia_estudiante'], 'idempotente'=>!$asistencia['creada']
            ]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    /** @return array<string,mixed>|null */
    private function persona(string $tipo, string $ci): ?array
    {
        if ($tipo === 'profesor') {
            $sql = "SELECT p.id_profesor,p.user_id_global,
                    (SELECT ht.id_huella FROM huellas_templates ht WHERE ht.activo=1
                      AND (ht.id_profesor=p.id_profesor OR ht.user_id_global=p.user_id_global)
                      ORDER BY ht.id_huella DESC LIMIT 1) id_huella
                    FROM profesores p WHERE p.cedula_identidad=:ci AND p.activo=1 LIMIT 1";
        } else {
            $sql = "SELECT e.id_estudiante,e.id_grado,e.user_id_global,
                    COALESCE(e.huella_id,(SELECT ht.id_huella FROM huellas_templates ht WHERE ht.activo=1
                      AND (ht.id_estudiante=e.id_estudiante OR ht.user_id_global=e.user_id_global)
                      ORDER BY ht.id_huella DESC LIMIT 1)) id_huella
                    FROM estudiantes e WHERE e.cedula_identidad=:ci AND e.activo=1 LIMIT 1";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ci'=>$ci]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function aulaActiva(int $idAula): bool
    {
        $stmt=$this->db->prepare('SELECT 1 FROM aulas WHERE id_aula=:aula AND activo=1');
        $stmt->execute([':aula'=>$idAula]);
        return (bool)$stmt->fetchColumn();
    }

    /** @return array<string,string> */
    private function clase(int $idClase): array
    {
        $stmt=$this->db->prepare('SELECT id_horario,fecha,hora_inicio,hora_fin FROM clases_diarias WHERE id_clase=:clase');
        $stmt->execute([':clase'=>$idClase]);
        $fila=$stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) throw new RuntimeException('La clase creada no pudo recuperarse.');
        return $fila;
    }
}
