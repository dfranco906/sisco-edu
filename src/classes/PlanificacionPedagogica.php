<?php
require_once __DIR__ . '/../config/api_auth.php';
require_once __DIR__ . '/PlanProgrammingRules.php';

class PedagogiaException extends RuntimeException
{
    public int $http;
    public function __construct(string $message, int $http = 422)
    {
        parent::__construct($message);
        $this->http = $http;
    }
}

class PlanificacionPedagogica
{
    private PDO $db;
    private array $usuario;

    public function __construct(PDO $db, array $usuario)
    {
        $this->db = $db;
        $this->usuario = $usuario;
    }

    public function asignacionesDisponibles(): array
    {
        $sql = "SELECT ad.id_asignacion, ad.id_profesor, ad.id_materia, ad.id_grado,
                       ad.carga_horaria, ad.anio_lectivo,
                       CONCAT(p.nombre, ' ', p.apellido) profesor, m.nombre materia,
                       g.nombre grado, a.nombre aula,
                       CONCAT(m.nombre, ' - ', g.nombre, ' - ', p.nombre, ' ', p.apellido, ' - ', ad.anio_lectivo) descripcion
                FROM asignacion_docente ad
                INNER JOIN profesores p ON p.id_profesor=ad.id_profesor AND p.activo=1
                INNER JOIN materias m ON m.id_materia=ad.id_materia AND m.activo=1
                INNER JOIN grados g ON g.id_grado=ad.id_grado AND g.activo=1
                INNER JOIN aulas a ON a.id_aula=g.id_aula AND a.activo=1
                WHERE ad.activo=1";
        $params = [];
        if ($this->usuario['rol'] === 'Profesor') {
            $idProfesor = profesorDeUsuario($this->db, $this->usuario);
            if (!$idProfesor) throw new PedagogiaException('La cuenta no esta vinculada a un profesor activo.', 403);
            $sql .= ' AND ad.id_profesor=:id_profesor';
            $params[':id_profesor'] = $idProfesor;
        }
        $sql .= ' ORDER BY ad.anio_lectivo DESC, m.nombre, g.nombre, p.apellido, p.nombre';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listar(array $filtros = []): array
    {
        $sql = "SELECT pa.id_plan, pa.id_asignacion, pa.anio, pa.estado,
                       pa.competencia_general, pa.competencia_especifica, pa.observaciones,
                       pa.created_at, pa.updated_at, ad.id_profesor, ad.id_materia, ad.id_grado,
                       CONCAT(p.nombre, ' ', p.apellido) profesor, m.nombre materia, g.nombre grado, a.nombre aula,
                       (SELECT COUNT(*) FROM plan_unidades u WHERE u.id_plan=pa.id_plan) unidades,
                       (SELECT COUNT(*) FROM plan_capacidades c JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE u.id_plan=pa.id_plan) capacidades,
                       (SELECT COUNT(*) FROM plan_temas t JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE u.id_plan=pa.id_plan) temas,
                       (SELECT COUNT(*) FROM plan_indicadores i JOIN plan_temas t ON t.id_tema=i.id_tema JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE u.id_plan=pa.id_plan) indicadores
                FROM planes_anuales pa
                INNER JOIN asignacion_docente ad ON ad.id_asignacion=pa.id_asignacion
                INNER JOIN profesores p ON p.id_profesor=ad.id_profesor
                INNER JOIN materias m ON m.id_materia=ad.id_materia
                INNER JOIN grados g ON g.id_grado=ad.id_grado
                INNER JOIN aulas a ON a.id_aula=g.id_aula WHERE 1=1";
        $params = [];
        if ($this->usuario['rol'] === 'Profesor') {
            $idProfesor = profesorDeUsuario($this->db, $this->usuario);
            if (!$idProfesor) throw new PedagogiaException('La cuenta no esta vinculada a un profesor activo.', 403);
            $sql .= ' AND ad.id_profesor=:id_profesor';
            $params[':id_profesor'] = $idProfesor;
        }
        if (!empty($filtros['estado']) && in_array($filtros['estado'], ['BORRADOR','PUBLICADO','ARCHIVADO'], true)) {
            $sql .= ' AND pa.estado=:estado';
            $params[':estado'] = $filtros['estado'];
        }
        if (!empty($filtros['anio'])) {
            $sql .= ' AND pa.anio=:anio';
            $params[':anio'] = (int) $filtros['anio'];
        }
        $sql .= ' ORDER BY pa.anio DESC, m.nombre, g.nombre, pa.id_plan DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crearPlan(array $datos): int
    {
        $idAsignacion = $this->id($datos['id_asignacion'] ?? null, 'Asignacion');
        $asignacion = asegurarAccesoAsignacion($this->db, $this->usuario, $idAsignacion, true);
        $anio = (int) ($datos['anio'] ?? 0);
        if ($anio !== (int) $asignacion['anio_lectivo']) {
            throw new PedagogiaException('El anio del plan debe coincidir con el anio lectivo de la asignacion.');
        }
        try {
            $stmt = $this->db->prepare("INSERT INTO planes_anuales
                (id_asignacion,anio,competencia_general,competencia_especifica,observaciones,created_by)
                VALUES (:asignacion,:anio,:general,:especifica,:observaciones,:creador)");
            $stmt->execute([
                ':asignacion'=>$idAsignacion, ':anio'=>$anio,
                ':general'=>$this->nullable($datos['competencia_general'] ?? null),
                ':especifica'=>$this->nullable($datos['competencia_especifica'] ?? null),
                ':observaciones'=>$this->nullable($datos['observaciones'] ?? null),
                ':creador'=>$this->usuario['id_usuario']
            ]);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') throw new PedagogiaException('Ya existe un plan para esa asignacion y anio.', 409);
            throw $e;
        }
    }

    public function actualizarPlan(int $idPlan, array $datos): void
    {
        $this->transaccion(function () use ($idPlan,$datos): void {
            $this->planParaMutacion($idPlan);
            $stmt = $this->db->prepare("UPDATE planes_anuales SET competencia_general=:general,
                competencia_especifica=:especifica, observaciones=:observaciones,
                institucion_fuente=:institucion,materia_fuente=:materia_fuente,profesor_fuente=:profesor_fuente,
                curso_fuente=:curso_fuente,turno_fuente=:turno_fuente,anio_fuente=:anio_fuente,dias_clase_fuente=:dias_clase_fuente WHERE id_plan=:id");
            $stmt->execute([
                ':general'=>$this->nullable($datos['competencia_general'] ?? null),
                ':especifica'=>$this->nullable($datos['competencia_especifica'] ?? null),
                ':observaciones'=>$this->nullable($datos['observaciones'] ?? null),
                ':institucion'=>$this->nullableMax($datos['institucion_fuente']??null,255,'Institucion fuente'),
                ':materia_fuente'=>$this->nullableMax($datos['materia_fuente']??null,255,'Materia fuente'),
                ':profesor_fuente'=>$this->nullableMax($datos['profesor_fuente']??null,255,'Profesor fuente'),
                ':curso_fuente'=>$this->nullableMax($datos['curso_fuente']??null,100,'Curso fuente'),
                ':turno_fuente'=>$this->nullableMax($datos['turno_fuente']??null,100,'Turno fuente'),
                ':anio_fuente'=>$this->anioFuente($datos['anio_fuente']??null),
                ':dias_clase_fuente'=>$this->nullableMax($datos['dias_clase_fuente']??null,255,'Dias de clase fuente'), ':id'=>$idPlan
            ]);
        });
    }

    public function cambiarEstado(int $idPlan, string $estado): void
    {
        $this->transaccion(function () use ($idPlan, $estado): void {
            $plan=$this->planParaMutacion($idPlan);
            if (!in_array($estado, ['BORRADOR','PUBLICADO','ARCHIVADO'], true)) throw new PedagogiaException('Estado no valido.');
            if ($estado === 'PUBLICADO') $this->exigirCobertura($idPlan,(int)$plan['anio']);
            $this->db->prepare('UPDATE planes_anuales SET estado=:estado WHERE id_plan=:id')->execute([':estado'=>$estado, ':id'=>$idPlan]);
        });
    }

    public function obtenerPlan(int $idPlan): array
    {
        $plan = $this->planConAcceso($idPlan, false);
        $origin=null;if(!empty($plan['importacion_origen_json'])){try{$decoded=json_decode($plan['importacion_origen_json'],true,128,JSON_THROW_ON_ERROR);$source=$decoded['source']??[];$origin=['importado'=>true,'archivo'=>$source['filename']??null,'formato'=>$source['format']??null,'paginas'=>$source['pages']??null];}catch(Throwable){$origin=['importado'=>true,'archivo'=>null,'formato'=>null,'paginas'=>null];}}
        $plan['importacion_origen']=$origin;unset($plan['importacion_origen_json']);
        $plan['unidades'] = $this->filas("SELECT * FROM plan_unidades WHERE id_plan=:id ORDER BY orden,id_unidad", [':id'=>$idPlan]);
        $idsUnidades = array_column($plan['unidades'], 'id_unidad');
        $capacidades = $this->porIds("SELECT * FROM plan_capacidades WHERE id_unidad IN (%s) ORDER BY orden,id_capacidad", $idsUnidades);
        $temas = $this->porIds("SELECT * FROM plan_temas WHERE id_capacidad IN (%s) ORDER BY orden,id_tema", array_column($capacidades, 'id_capacidad'));
        $indicadores = $this->porIds("SELECT * FROM plan_indicadores WHERE id_tema IN (%s) ORDER BY orden,id_indicador", array_column($temas, 'id_tema'));
        $programaciones = $this->porIds("SELECT * FROM plan_tema_programacion WHERE id_tema IN (%s) ORDER BY orden,id_programacion", array_column($temas, 'id_tema'));
        $procedimientos = $this->porIds("SELECT tp.id_tema,tp.id_procedimiento,p.nombre,tp.texto_fuente,tp.orden FROM plan_tema_procedimientos tp JOIN procedimientos_evaluativos p ON p.id_procedimiento=tp.id_procedimiento WHERE tp.id_tema IN (%s) ORDER BY tp.orden,tp.id_procedimiento", array_column($temas, 'id_tema'));
        $instrumentos = $this->porIds("SELECT ti.id_tema,ti.id_instrumento,i.nombre,ti.texto_fuente,ti.orden FROM plan_tema_instrumentos ti JOIN instrumentos_evaluativos i ON i.id_instrumento=ti.id_instrumento WHERE ti.id_tema IN (%s) ORDER BY ti.orden,ti.id_instrumento", array_column($temas, 'id_tema'));

        foreach ($temas as &$tema) {
            $id = (int)$tema['id_tema'];
            $tema['indicadores'] = array_values(array_filter($indicadores, fn($x)=>(int)$x['id_tema']===$id));
            $tema['programaciones'] = array_values(array_filter($programaciones, fn($x)=>(int)$x['id_tema']===$id));
            $tema['procedimientos'] = array_values(array_filter($procedimientos, fn($x)=>(int)$x['id_tema']===$id));
            $tema['instrumentos'] = array_values(array_filter($instrumentos, fn($x)=>(int)$x['id_tema']===$id));
        }
        unset($tema);
        foreach ($capacidades as &$capacidad) {
            $id=(int)$capacidad['id_capacidad'];
            $capacidad['temas']=array_values(array_filter($temas, fn($x)=>(int)$x['id_capacidad']===$id));
        }
        unset($capacidad);
        foreach ($plan['unidades'] as &$unidad) {
            $id=(int)$unidad['id_unidad'];
            $unidad['capacidades']=array_values(array_filter($capacidades, fn($x)=>(int)$x['id_unidad']===$id));
        }
        unset($unidad);
        $plan['cobertura']=$this->coberturaPublicacion($idPlan,(int)$plan['anio']);
        return $plan;
    }

    public function guardarElemento(string $tipo, array $datos): int
    {
        $config = $this->configElemento($tipo);
        $id = isset($datos[$config['pk']]) && $datos[$config['pk']] !== '' ? $this->id($datos[$config['pk']], 'Registro') : null;
        $idPadre = $this->id($datos[$config['parent']] ?? null, 'Registro padre');
        $idPlan = $this->planDePadre($tipo, $idPadre);
        $plan = $this->planConAcceso($idPlan, true);
        if ($plan['estado'] === 'ARCHIVADO') throw new PedagogiaException('El plan esta archivado.', 409);

        $valores = [];
        foreach ($config['fields'] as $campo=>$regla) {
            $valor = $datos[$campo] ?? null;
            if ($regla === 'required') $valor = $this->texto($valor, ucfirst(str_replace('_',' ',$campo)), 10000);
            elseif ($regla === 'short') $valor = $this->texto($valor, ucfirst(str_replace('_',' ',$campo)), 255);
            elseif ($regla === 'code') $valor = $this->nullableMax($valor,32,'Codigo');
            elseif ($regla === 'number') $valor = ($valor === '' || $valor === null) ? null : (float)$valor;
            elseif ($regla === 'date') $valor = $this->fechaNullable($valor);
            else $valor = $this->nullable($valor);
            $valores[$campo]=$valor;
        }
        if ($tipo === 'unidad' && $valores['proceso_inicio'] && $valores['proceso_fin'] && $valores['proceso_fin'] < $valores['proceso_inicio']) {
            throw new PedagogiaException('El fin del proceso no puede ser anterior al inicio.');
        }
        $this->db->beginTransaction();
        try {
            $plan=$this->planParaMutacion($idPlan);
            if ($id) {
                $actual = $this->fila("SELECT {$config['parent']} FROM {$config['table']} WHERE {$config['pk']}=:id", [':id'=>$id]);
                if (!$actual || (int)$actual[$config['parent']] !== $idPadre) throw new PedagogiaException('El registro no pertenece al padre indicado.', 409);
                $set=[]; $params=[':id'=>$id];
                foreach ($valores as $campo=>$valor) { $set[]="$campo=:$campo"; $params[":$campo"]=$valor; }
                $this->db->prepare("UPDATE {$config['table']} SET ".implode(',',$set)." WHERE {$config['pk']}=:id")->execute($params);
            } else {
                $orden = isset($datos['orden']) ? max(1,(int)$datos['orden']) : $this->siguienteOrden($config['table'],$config['parent'],$idPadre);
                $columnas=array_merge([$config['parent']],array_keys($valores),['orden']);
                $params=[':parent'=>$idPadre, ':orden'=>$orden];
                foreach ($valores as $campo=>$valor) $params[":$campo"]=$valor;
                $marcadores=array_merge([':parent'],array_map(fn($x)=>":$x",array_keys($valores)),[':orden']);
                $this->db->prepare("INSERT INTO {$config['table']} (".implode(',',$columnas).") VALUES (".implode(',',$marcadores).")")->execute($params);
                $id=(int)$this->db->lastInsertId();
            }
            if($plan['estado']==='PUBLICADO')$this->exigirCobertura($idPlan,(int)$plan['anio']);
            $this->db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function eliminarElemento(string $tipo, int $id, bool $confirmado): array
    {
        if (!$confirmado) throw new PedagogiaException('Debe confirmar la eliminacion de la estructura y sus hijos.', 409);
        $idPlan = $this->planDeElemento($tipo, $id);
        $plan = $this->planConAcceso($idPlan, true);
        if ($plan['estado'] === 'ARCHIVADO') throw new PedagogiaException('El plan esta archivado.', 409);
        $idsTemas = $this->idsTemasElemento($tipo, $id);
        if ($tipo === 'indicador') {
            $usado=$this->db->prepare('SELECT COUNT(*) FROM clase_diaria_indicadores WHERE id_indicador=:id');
            $usado->execute([':id'=>$id]);
            if((int)$usado->fetchColumn()>0)throw new PedagogiaException('No se puede eliminar un indicador usado por una clase diaria.',409);
        }
        if ($idsTemas) {
            $marcas=implode(',',array_fill(0,count($idsTemas),'?'));
            $stmt=$this->db->prepare("SELECT COUNT(*) FROM clases_diarias WHERE id_tema IN ($marcas)");
            $stmt->execute($idsTemas);
            if ((int)$stmt->fetchColumn()>0) throw new PedagogiaException('No se puede eliminar contenido usado por clases diarias. Archive el plan o reprograme la clase.',409);
        }
        $this->db->beginTransaction();
        try {
            $plan=$this->planParaMutacion($idPlan);
            $resumen=$this->resumenDescendientes($tipo,$id);
            if ($tipo==='indicador') {
                $this->db->prepare('DELETE FROM plan_indicadores WHERE id_indicador=:id')->execute([':id'=>$id]);
            } else {
                $this->eliminarSubarbol($tipo,$id,$idsTemas);
            }
            if($plan['estado']==='PUBLICADO')$this->exigirCobertura($idPlan,(int)$plan['anio']);
            $this->db->commit();
            return $resumen;
        } catch(Throwable $e) {
            if($this->db->inTransaction())$this->db->rollBack();
            throw $e;
        }
    }

    public function reordenar(string $tipo, array $ids): void
    {
        $config=$this->configElemento($tipo);
        $ids=array_values(array_unique(array_filter(array_map('intval',$ids))));
        if(!$ids)throw new PedagogiaException('No se recibieron elementos para ordenar.');
        $primer=$this->fila("SELECT {$config['parent']} padre FROM {$config['table']} WHERE {$config['pk']}=:id",[':id'=>$ids[0]]);
        if(!$primer)throw new PedagogiaException('Elemento no encontrado.',404);
        $idPlan=$this->planDeElemento($tipo,$ids[0]); $this->planConAcceso($idPlan,true);
        $marcas=implode(',',array_fill(0,count($ids),'?'));
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM {$config['table']} WHERE {$config['pk']} IN ($marcas) AND {$config['parent']}=?");
        $stmt->execute(array_merge($ids,[(int)$primer['padre']]));
        if((int)$stmt->fetchColumn()!==count($ids))throw new PedagogiaException('Los elementos no pertenecen al mismo nivel.',409);
        $this->db->beginTransaction();
        try { $this->planParaMutacion($idPlan); $up=$this->db->prepare("UPDATE {$config['table']} SET orden=:orden WHERE {$config['pk']}=:id"); foreach($ids as $i=>$x)$up->execute([':orden'=>$i+1,':id'=>$x]); $this->db->commit(); }
        catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function catalogos(): array
    {
        return [
            'procedimientos'=>$this->filas('SELECT id_procedimiento,nombre FROM procedimientos_evaluativos WHERE activo=1 ORDER BY nombre'),
            'instrumentos'=>$this->filas('SELECT id_instrumento,nombre FROM instrumentos_evaluativos WHERE activo=1 ORDER BY nombre')
        ];
    }

    public function crearCatalogo(string $tipo, string $nombre): array
    {
        if(!in_array($tipo,['procedimiento','instrumento'],true))throw new PedagogiaException('Catalogo no valido.');
        $nombre=$this->texto($nombre,'Nombre',150);
        $tabla=$tipo==='procedimiento'?'procedimientos_evaluativos':'instrumentos_evaluativos';
        $pk=$tipo==='procedimiento'?'id_procedimiento':'id_instrumento';
        try {$this->db->prepare("INSERT INTO $tabla (nombre) VALUES (:nombre)")->execute([':nombre'=>$nombre]);}
        catch(PDOException $e){if($e->getCode()==='23000')throw new PedagogiaException('Ese elemento ya existe.',409);throw $e;}
        return [$pk=>(int)$this->db->lastInsertId(),'nombre'=>$nombre];
    }

    public function guardarEvaluacion(int $idTema, array $procedimientos, array $instrumentos): void
    {
        $this->planConAcceso($this->planDeElemento('tema',$idTema),true);
        $procedimientos=$this->normalizarRelaciones($procedimientos,'id_procedimiento');
        $instrumentos=$this->normalizarRelaciones($instrumentos,'id_instrumento');
        $this->validarCatalogo('procedimientos_evaluativos','id_procedimiento',array_column($procedimientos,'id'));
        $this->validarCatalogo('instrumentos_evaluativos','id_instrumento',array_column($instrumentos,'id'));
        $this->db->beginTransaction();
        try {
            $this->planParaMutacion($this->planDeElemento('tema',$idTema));
            $this->db->prepare('DELETE FROM plan_tema_procedimientos WHERE id_tema=:id')->execute([':id'=>$idTema]);
            $this->db->prepare('DELETE FROM plan_tema_instrumentos WHERE id_tema=:id')->execute([':id'=>$idTema]);
            $p=$this->db->prepare('INSERT INTO plan_tema_procedimientos (id_tema,id_procedimiento,texto_fuente,orden) VALUES (:tema,:id,:texto,:orden)'); foreach($procedimientos as $rel)$p->execute([':tema'=>$idTema,':id'=>$rel['id'],':texto'=>$rel['texto_fuente'],':orden'=>$rel['orden']]);
            $i=$this->db->prepare('INSERT INTO plan_tema_instrumentos (id_tema,id_instrumento,texto_fuente,orden) VALUES (:tema,:id,:texto,:orden)'); foreach($instrumentos as $rel)$i->execute([':tema'=>$idTema,':id'=>$rel['id'],':texto'=>$rel['texto_fuente'],':orden'=>$rel['orden']]);
            $this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function guardarProgramacion(array $datos): int
    {
        return $this->transaccion(function () use ($datos): int {
            $row=$this->normalizarProgramacion($datos);
            $plan=$this->planParaMutacion($this->planDeElemento('tema',$row['id_tema']));
            $id=empty($datos['id_programacion'])?null:$this->id($datos['id_programacion'],'Programacion');
            $rows=$this->programacionesPlan((int)$plan['id_plan']);
            if($id && !array_filter($rows,fn($r)=>(int)$r['id_programacion']===$id && (int)$r['id_tema']===$row['id_tema']))throw new PedagogiaException('Programacion no encontrada.',404);
            $rows=array_values(array_filter($rows,fn($r)=>!$id || (int)$r['id_programacion']!==$id));
            $rows[]=$row;
            $this->validarProgramaciones($rows,(int)$plan['id_plan'],(int)$plan['anio']);
            if($id){
                $this->db->prepare('UPDATE plan_tema_programacion SET fecha_inicio=?,fecha_fin=?,horas_catedra_planificadas=?,observaciones=? WHERE id_programacion=?')->execute([$row['fecha_inicio'],$row['fecha_fin'],$row['horas_catedra_planificadas'],$row['observaciones'],$id]);
            }else $id=$this->insertarProgramacion($row);
            if($plan['estado']==='PUBLICADO')$this->exigirCobertura((int)$plan['id_plan'],(int)$plan['anio']);
            return $id;
        });
    }

    /** Reemplaza la programación anual completa de forma atómica. */
    public function guardarProgramaciones(int $idPlan,array $rows): array
    {
        return $this->transaccion(function () use ($idPlan,$rows): array {
            $plan=$this->planParaMutacion($idPlan);
            if(!array_is_list($rows))throw new PedagogiaException('La programación anual debe ser una lista.');
            $normalized=array_map(fn($r)=>is_array($r)?$this->normalizarProgramacion($r):throw new PedagogiaException('Programación no válida.'),$rows);
            $this->validarProgramaciones($normalized,$idPlan,(int)$plan['anio']);
            $this->db->prepare('DELETE pr FROM plan_tema_programacion pr JOIN plan_temas t ON t.id_tema=pr.id_tema JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE u.id_plan=?')->execute([$idPlan]);
            foreach($normalized as $row)$this->insertarProgramacion($row);
            if($plan['estado']==='PUBLICADO')$this->exigirCobertura($idPlan,(int)$plan['anio']);
            return $this->coberturaPublicacion($idPlan,(int)$plan['anio']);
        });
    }

    public function eliminarProgramacion(int $id): void
    {
        $this->transaccion(function () use ($id): void {
            $fila=$this->fila('SELECT id_tema FROM plan_tema_programacion WHERE id_programacion=:id',[':id'=>$id]);
            if(!$fila)throw new PedagogiaException('Programacion no encontrada.',404);
            $plan=$this->planParaMutacion($this->planDeElemento('tema',(int)$fila['id_tema']));
            $this->db->prepare('DELETE FROM plan_tema_programacion WHERE id_programacion=:id')->execute([':id'=>$id]);
            if($plan['estado']==='PUBLICADO')$this->exigirCobertura((int)$plan['id_plan'],(int)$plan['anio']);
        });
    }

    private function temasPlan(int $idPlan): array
    {
        return $this->filas('SELECT t.id_tema,(SELECT COUNT(*) FROM plan_indicadores i WHERE i.id_tema=t.id_tema) indicadores FROM plan_temas t JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE u.id_plan=?',[$idPlan]);
    }

    private function programacionesPlan(int $idPlan): array
    {
        return $this->filas('SELECT pr.*,pr.id_tema topic_key FROM plan_tema_programacion pr JOIN plan_temas t ON t.id_tema=pr.id_tema JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE u.id_plan=?',[$idPlan]);
    }

    private function validarProgramaciones(array $rows,int $idPlan,int $year): void
    {
        $check=PlanProgrammingRules::inspect($rows,array_column($this->temasPlan($idPlan),'id_tema'),$year);
        if($check['errores'])throw new PedagogiaException($check['errores'][0],409);
    }

    private function coberturaPublicacion(int $idPlan,int $year): array
    {
        $topics=$this->temasPlan($idPlan);
        $check=PlanProgrammingRules::inspect($this->programacionesPlan($idPlan),array_column($topics,'id_tema'),$year);
        $total=count($topics);$indicators=count(array_filter($topics,fn($t)=>(int)$t['indicadores']>0));$scheduled=count($check['temas_validos']);
        $message='Listo para publicar.';
        if(!$total)$message='No se puede publicar: agregue al menos un tema.';
        elseif($indicators<$total)$message='No se puede publicar: '.($total-$indicators).' de '.$total.' temas todavía no tienen indicadores.';
        elseif($scheduled<$total)$message='No se puede publicar: '.($total-$scheduled).' de '.$total.' temas todavía no tienen programación exacta válida.';
        elseif($check['errores'])$message='No se puede publicar: '.$check['errores'][0];
        return ['total_temas'=>$total,'temas_con_indicadores'=>$indicators,'temas_programados'=>$scheduled,'temas_sin_indicadores'=>$total-$indicators,'temas_sin_programacion'=>$total-$scheduled,'solapamientos'=>$check['solapamientos'],'fechas_invalidas'=>$check['fechas_invalidas'],'duplicados'=>$check['duplicados'],'can_publish'=>$total>0 && $indicators===$total && $scheduled===$total && !$check['errores'],'mensaje'=>$message];
    }

    private function exigirCobertura(int $idPlan,int $year): void
    {
        $coverage=$this->coberturaPublicacion($idPlan,$year);
        if(!$coverage['can_publish'])throw new PedagogiaException($coverage['mensaje'],409);
    }

    private function normalizarProgramacion(array $data): array
    {
        $id=$this->id($data['id_tema']??null,'Tema');
        $hours=$data['horas_catedra_planificadas']??null;
        if($hours==='')$hours=null;
        if($hours!==null && (!is_numeric($hours) || !is_finite((float)$hours) || (float)$hours<0))throw new PedagogiaException('Horas programadas no válidas.');
        return ['id_tema'=>$id,'topic_key'=>(string)$id,'fecha_inicio'=>$data['fecha_inicio']??null,'fecha_fin'=>$data['fecha_fin']??null,'horas_catedra_planificadas'=>$hours===null?null:(float)$hours,'observaciones'=>$this->nullableMax($data['observaciones']??null,10000,'Observaciones')];
    }

    private function insertarProgramacion(array $row): int
    {
        $order=$this->siguienteOrden('plan_tema_programacion','id_tema',$row['id_tema']);
        $this->db->prepare('INSERT INTO plan_tema_programacion (id_tema,fecha_inicio,fecha_fin,horas_catedra_planificadas,observaciones,orden) VALUES (?,?,?,?,?,?)')->execute([$row['id_tema'],$row['fecha_inicio'],$row['fecha_fin'],$row['horas_catedra_planificadas'],$row['observaciones'],$order]);
        return (int)$this->db->lastInsertId();
    }

    private function planParaMutacion(int $idPlan): array
    {
        $this->db->prepare('SELECT id_plan FROM planes_anuales WHERE id_plan=? FOR UPDATE')->execute([$idPlan]);
        return $this->planConAcceso($idPlan,true);
    }

    private function transaccion(callable $action): mixed
    {
        $own=!$this->db->inTransaction();if($own)$this->db->beginTransaction();
        try{$result=$action();if($own)$this->db->commit();return $result;}
        catch(Throwable $e){if($own && $this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    private function planConAcceso(int $idPlan, bool $escritura): array
    {
        $stmt=$this->db->prepare("SELECT pa.*,ad.id_profesor,ad.id_materia,ad.id_grado,ad.activo asignacion_activa,
            CONCAT(p.nombre,' ',p.apellido) profesor,m.nombre materia,g.nombre grado,a.nombre aula
            FROM planes_anuales pa JOIN asignacion_docente ad ON ad.id_asignacion=pa.id_asignacion
            JOIN profesores p ON p.id_profesor=ad.id_profesor JOIN materias m ON m.id_materia=ad.id_materia
            JOIN grados g ON g.id_grado=ad.id_grado JOIN aulas a ON a.id_aula=g.id_aula WHERE pa.id_plan=:id LIMIT 1");
        $stmt->execute([':id'=>$idPlan]); $plan=$stmt->fetch(PDO::FETCH_ASSOC);
        if(!$plan)throw new PedagogiaException('Plan no encontrado.',404);
        asegurarAccesoAsignacion($this->db,$this->usuario,(int)$plan['id_asignacion'],$escritura);
        if($escritura && $plan['estado']==='ARCHIVADO')throw new PedagogiaException('El plan está archivado y es de solo lectura.',409);
        return $plan;
    }

    private function planDePadre(string $tipo,int $id): int
    {
        return match($tipo){
            'unidad'=>$id,
            'capacidad'=>(int)($this->fila('SELECT id_plan FROM plan_unidades WHERE id_unidad=:id',[':id'=>$id])['id_plan']??0),
            'tema'=>(int)($this->fila('SELECT u.id_plan FROM plan_capacidades c JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE c.id_capacidad=:id',[':id'=>$id])['id_plan']??0),
            'indicador'=>(int)($this->fila('SELECT u.id_plan FROM plan_temas t JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad JOIN plan_unidades u ON u.id_unidad=c.id_unidad WHERE t.id_tema=:id',[':id'=>$id])['id_plan']??0),
            default=>0
        } ?: throw new PedagogiaException('Registro padre no encontrado.',404);
    }

    private function planDeElemento(string $tipo,int $id): int
    {
        $config=$this->configElemento($tipo);
        $fila=$this->fila("SELECT {$config['parent']} padre FROM {$config['table']} WHERE {$config['pk']}=:id",[':id'=>$id]);
        if(!$fila)throw new PedagogiaException('Elemento no encontrado.',404);
        return $this->planDePadre($tipo,(int)$fila['padre']);
    }

    private function configElemento(string $tipo): array
    {
        return match($tipo){
            'unidad'=>['table'=>'plan_unidades','pk'=>'id_unidad','parent'=>'id_plan','fields'=>['codigo'=>'code','nombre'=>'short','descripcion'=>'nullable','horas_catedra'=>'number','tiempo_texto'=>'nullable','proceso_texto'=>'nullable','proceso_inicio'=>'date','proceso_fin'=>'date','area_transversal'=>'nullable','metodologia'=>'nullable','medios_verificacion'=>'nullable']],
            'capacidad'=>['table'=>'plan_capacidades','pk'=>'id_capacidad','parent'=>'id_unidad','fields'=>['descripcion'=>'required','proceso_desarrollo'=>'nullable']],
            'tema'=>['table'=>'plan_temas','pk'=>'id_tema','parent'=>'id_capacidad','fields'=>['codigo'=>'code','titulo'=>'short','contenido'=>'nullable','horas_catedra'=>'number','tiempo_texto'=>'nullable','fecha_texto'=>'nullable']],
            'indicador'=>['table'=>'plan_indicadores','pk'=>'id_indicador','parent'=>'id_tema','fields'=>['codigo'=>'code','descripcion'=>'required']],
            default=>throw new PedagogiaException('Tipo de elemento no valido.')
        };
    }

    private function idsTemasElemento(string $tipo,int $id): array
    {
        return match($tipo){
            'unidad'=>array_map('intval',array_column($this->filas('SELECT t.id_tema FROM plan_temas t JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad WHERE c.id_unidad=:id',[':id'=>$id]),'id_tema')),
            'capacidad'=>array_map('intval',array_column($this->filas('SELECT id_tema FROM plan_temas WHERE id_capacidad=:id',[':id'=>$id]),'id_tema')),
            'tema'=>[$id], 'indicador'=>[], default=>[]
        };
    }

    private function eliminarSubarbol(string $tipo,int $id,array $idsTemas): void
    {
        if($idsTemas){$m=implode(',',array_fill(0,count($idsTemas),'?'));$this->db->prepare("DELETE FROM plan_indicadores WHERE id_tema IN ($m)")->execute($idsTemas);$this->db->prepare("DELETE FROM plan_temas WHERE id_tema IN ($m)")->execute($idsTemas);}
        if($tipo==='unidad'){$this->db->prepare('DELETE FROM plan_capacidades WHERE id_unidad=:id')->execute([':id'=>$id]);$this->db->prepare('DELETE FROM plan_unidades WHERE id_unidad=:id')->execute([':id'=>$id]);}
        elseif($tipo==='capacidad')$this->db->prepare('DELETE FROM plan_capacidades WHERE id_capacidad=:id')->execute([':id'=>$id]);
        elseif($tipo==='tema' && !$idsTemas)$this->db->prepare('DELETE FROM plan_temas WHERE id_tema=:id')->execute([':id'=>$id]);
    }

    private function resumenDescendientes(string $tipo,int $id): array
    {
        if($tipo==='indicador')return ['indicadores'=>1];
        $temas=$this->idsTemasElemento($tipo,$id); $indicadores=0;
        if($temas){$m=implode(',',array_fill(0,count($temas),'?'));$s=$this->db->prepare("SELECT COUNT(*) FROM plan_indicadores WHERE id_tema IN ($m)");$s->execute($temas);$indicadores=(int)$s->fetchColumn();}
        $capacidades=$tipo==='unidad'?(int)($this->fila('SELECT COUNT(*) total FROM plan_capacidades WHERE id_unidad=:id',[':id'=>$id])['total']??0):($tipo==='capacidad'?1:0);
        return ['unidades'=>$tipo==='unidad'?1:0,'capacidades'=>$capacidades,'temas'=>count($temas),'indicadores'=>$indicadores];
    }

    private function validarCatalogo(string $tabla,string $pk,array $ids): void
    {
        if(!$ids)return;$m=implode(',',array_fill(0,count($ids),'?'));$s=$this->db->prepare("SELECT COUNT(*) FROM $tabla WHERE $pk IN ($m) AND activo=1");$s->execute($ids);if((int)$s->fetchColumn()!==count($ids))throw new PedagogiaException('La seleccion contiene opciones inactivas o inexistentes.');
    }
    private function normalizarRelaciones(array $items,string $pk):array
    {
        $result=[];$seen=[];foreach(array_values($items) as $index=>$item){$id=is_array($item)?(int)($item['id']??$item[$pk]??0):(int)$item;if($id<1||isset($seen[$id]))continue;$text=is_array($item)?$this->nullableMax($item['texto_fuente']??null,150,'Texto fuente'):null;$order=is_array($item)?max(1,(int)($item['orden']??$index+1)):$index+1;$result[]=['id'=>$id,'texto_fuente'=>$text,'orden'=>$order];$seen[$id]=true;}usort($result,static fn(array $a,array $b):int=>$a['orden']<=>$b['orden']);foreach($result as $index=>&$row)$row['orden']=$index+1;unset($row);return$result;
    }
    private function siguienteOrden(string $tabla,string $padre,int $id): int{$s=$this->db->prepare("SELECT COALESCE(MAX(orden),0)+1 FROM $tabla WHERE $padre=:id");$s->execute([':id'=>$id]);return(int)$s->fetchColumn();}
    private function porIds(string $sql,array $ids): array{if(!$ids)return[];$m=implode(',',array_fill(0,count($ids),'?'));$s=$this->db->prepare(sprintf($sql,$m));$s->execute(array_values($ids));return$s->fetchAll(PDO::FETCH_ASSOC);}
    private function filas(string $sql,array $p=[]): array{$s=$this->db->prepare($sql);$s->execute($p);return$s->fetchAll(PDO::FETCH_ASSOC);}
    private function fila(string $sql,array $p=[]): ?array{$s=$this->db->prepare($sql);$s->execute($p);$x=$s->fetch(PDO::FETCH_ASSOC);return$x?:null;}
    private function id(mixed $v,string $n): int{$x=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$x)throw new PedagogiaException("$n no valido.");return(int)$x;}
    private function texto(mixed $v,string $n,int $max): string{$x=trim((string)$v);if($x===''||mb_strlen($x)>$max)throw new PedagogiaException("$n es obligatorio o demasiado extenso.");return$x;}
    private function nullable(mixed $v): ?string{$x=trim((string)$v);return$x===''?null:$x;}
    private function nullableMax(mixed $v,int $max,string $name):?string{$x=$this->nullable($v);if($x!==null&&mb_strlen($x)>$max)throw new PedagogiaException($name.' supera el limite permitido.');return$x;}
    private function anioFuente(mixed $v):?int{if($v===''||$v===null)return null;$year=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>2000,'max_range'=>2100]]);if($year===false)throw new PedagogiaException('Anio fuente no valido.');return(int)$year;}
    private function fecha(mixed $v,string $n): string{$x=(string)$v;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$x);if(!$d||$d->format('Y-m-d')!==$x)throw new PedagogiaException("$n no valida.");return$x;}
    private function fechaNullable(mixed $v): ?string{return($v===''||$v===null)?null:$this->fecha($v,'Fecha');}
}
?>
