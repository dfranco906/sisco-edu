<?php
require_once __DIR__ . '/../config/api_auth.php';

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
        $plan = $this->planConAcceso($idPlan, true);
        if ($plan['estado'] === 'ARCHIVADO') throw new PedagogiaException('Un plan archivado no puede editarse.', 409);
        $stmt = $this->db->prepare("UPDATE planes_anuales SET competencia_general=:general,
            competencia_especifica=:especifica, observaciones=:observaciones WHERE id_plan=:id");
        $stmt->execute([
            ':general'=>$this->nullable($datos['competencia_general'] ?? null),
            ':especifica'=>$this->nullable($datos['competencia_especifica'] ?? null),
            ':observaciones'=>$this->nullable($datos['observaciones'] ?? null), ':id'=>$idPlan
        ]);
    }

    public function cambiarEstado(int $idPlan, string $estado): void
    {
        $this->planConAcceso($idPlan, true);
        if (!in_array($estado, ['BORRADOR','PUBLICADO','ARCHIVADO'], true)) throw new PedagogiaException('Estado no valido.');
        if ($estado === 'PUBLICADO') {
            $stmt = $this->db->prepare("SELECT COUNT(DISTINCT t.id_tema) temas_completos
                FROM plan_unidades u
                JOIN plan_capacidades c ON c.id_unidad=u.id_unidad
                JOIN plan_temas t ON t.id_capacidad=c.id_capacidad
                WHERE u.id_plan=:id
                  AND EXISTS (SELECT 1 FROM plan_indicadores i WHERE i.id_tema=t.id_tema)
                  AND EXISTS (SELECT 1 FROM plan_tema_programacion pr WHERE pr.id_tema=t.id_tema)");
            $stmt->execute([':id'=>$idPlan]);
            $totales = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$totales || !(int)$totales['temas_completos']) {
                throw new PedagogiaException('Para publicar se requiere al menos un tema con indicador y programacion.', 409);
            }
        }
        $this->db->prepare('UPDATE planes_anuales SET estado=:estado WHERE id_plan=:id')->execute([':estado'=>$estado, ':id'=>$idPlan]);
    }

    public function obtenerPlan(int $idPlan): array
    {
        $plan = $this->planConAcceso($idPlan, false);
        $plan['unidades'] = $this->filas("SELECT * FROM plan_unidades WHERE id_plan=:id ORDER BY orden,id_unidad", [':id'=>$idPlan]);
        $idsUnidades = array_column($plan['unidades'], 'id_unidad');
        $capacidades = $this->porIds("SELECT * FROM plan_capacidades WHERE id_unidad IN (%s) ORDER BY orden,id_capacidad", $idsUnidades);
        $temas = $this->porIds("SELECT * FROM plan_temas WHERE id_capacidad IN (%s) ORDER BY orden,id_tema", array_column($capacidades, 'id_capacidad'));
        $indicadores = $this->porIds("SELECT * FROM plan_indicadores WHERE id_tema IN (%s) ORDER BY orden,id_indicador", array_column($temas, 'id_tema'));
        $programaciones = $this->porIds("SELECT * FROM plan_tema_programacion WHERE id_tema IN (%s) ORDER BY orden,id_programacion", array_column($temas, 'id_tema'));
        $procedimientos = $this->porIds("SELECT tp.id_tema,tp.id_procedimiento,p.nombre FROM plan_tema_procedimientos tp JOIN procedimientos_evaluativos p ON p.id_procedimiento=tp.id_procedimiento WHERE tp.id_tema IN (%s) ORDER BY p.nombre", array_column($temas, 'id_tema'));
        $instrumentos = $this->porIds("SELECT ti.id_tema,ti.id_instrumento,i.nombre FROM plan_tema_instrumentos ti JOIN instrumentos_evaluativos i ON i.id_instrumento=ti.id_instrumento WHERE ti.id_tema IN (%s) ORDER BY i.nombre", array_column($temas, 'id_tema'));

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
            $resumen=$this->resumenDescendientes($tipo,$id);
            if ($tipo==='indicador') {
                $this->db->prepare('DELETE FROM plan_indicadores WHERE id_indicador=:id')->execute([':id'=>$id]);
            } else {
                $this->eliminarSubarbol($tipo,$id,$idsTemas);
            }
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
        try { $up=$this->db->prepare("UPDATE {$config['table']} SET orden=:orden WHERE {$config['pk']}=:id"); foreach($ids as $i=>$x)$up->execute([':orden'=>$i+1,':id'=>$x]); $this->db->commit(); }
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
        $procedimientos=array_values(array_unique(array_filter(array_map('intval',$procedimientos))));
        $instrumentos=array_values(array_unique(array_filter(array_map('intval',$instrumentos))));
        $this->validarCatalogo('procedimientos_evaluativos','id_procedimiento',$procedimientos);
        $this->validarCatalogo('instrumentos_evaluativos','id_instrumento',$instrumentos);
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM plan_tema_procedimientos WHERE id_tema=:id')->execute([':id'=>$idTema]);
            $this->db->prepare('DELETE FROM plan_tema_instrumentos WHERE id_tema=:id')->execute([':id'=>$idTema]);
            $p=$this->db->prepare('INSERT INTO plan_tema_procedimientos (id_tema,id_procedimiento) VALUES (:tema,:id)'); foreach($procedimientos as $id)$p->execute([':tema'=>$idTema,':id'=>$id]);
            $i=$this->db->prepare('INSERT INTO plan_tema_instrumentos (id_tema,id_instrumento) VALUES (:tema,:id)'); foreach($instrumentos as $id)$i->execute([':tema'=>$idTema,':id'=>$id]);
            $this->db->commit();
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }

    public function guardarProgramacion(array $datos): int
    {
        $idTema=$this->id($datos['id_tema']??null,'Tema');
        $plan=$this->planConAcceso($this->planDeElemento('tema',$idTema),true);
        $inicio=$this->fecha($datos['fecha_inicio']??null,'Fecha de inicio');
        $fin=$this->fecha($datos['fecha_fin']??$inicio,'Fecha de fin');
        if($fin<$inicio)throw new PedagogiaException('La fecha final no puede ser anterior a la inicial.');
        if((int)substr($inicio,0,4)!==(int)$plan['anio']||(int)substr($fin,0,4)!==(int)$plan['anio'])throw new PedagogiaException('La programacion debe pertenecer al anio del plan.');
        $id=empty($datos['id_programacion'])?null:$this->id($datos['id_programacion'],'Programacion');
        $solape=$this->db->prepare("SELECT COUNT(*) FROM plan_tema_programacion pr
            JOIN plan_temas t ON t.id_tema=pr.id_tema JOIN plan_capacidades c ON c.id_capacidad=t.id_capacidad
            JOIN plan_unidades u ON u.id_unidad=c.id_unidad
            WHERE u.id_plan=:plan AND pr.id_tema<>:tema AND pr.fecha_inicio<=:fin AND pr.fecha_fin>=:inicio");
        $solape->execute([':plan'=>$plan['id_plan'],':tema'=>$idTema,':inicio'=>$inicio,':fin'=>$fin]);
        if((int)$solape->fetchColumn()>0)throw new PedagogiaException('El periodo se superpone con otro tema del mismo plan.',409);
        $params=[':tema'=>$idTema,':inicio'=>$inicio,':fin'=>$fin,':horas'=>($datos['horas_catedra_planificadas']??'')===''?null:(float)$datos['horas_catedra_planificadas'],':obs'=>$this->nullable($datos['observaciones']??null)];
        try {
            if($id){$params[':id']=$id;$stmt=$this->db->prepare('UPDATE plan_tema_programacion SET fecha_inicio=:inicio,fecha_fin=:fin,horas_catedra_planificadas=:horas,observaciones=:obs WHERE id_programacion=:id AND id_tema=:tema');$stmt->execute($params);if(!$stmt->rowCount()&&!$this->fila('SELECT id_programacion FROM plan_tema_programacion WHERE id_programacion=:id AND id_tema=:tema',[':id'=>$id,':tema'=>$idTema]))throw new PedagogiaException('Programacion no encontrada.',404);}
            else{$params[':orden']=$this->siguienteOrden('plan_tema_programacion','id_tema',$idTema);$this->db->prepare('INSERT INTO plan_tema_programacion (id_tema,fecha_inicio,fecha_fin,horas_catedra_planificadas,observaciones,orden) VALUES (:tema,:inicio,:fin,:horas,:obs,:orden)')->execute($params);$id=(int)$this->db->lastInsertId();}
        }catch(PDOException $e){if($e->getCode()==='23000')throw new PedagogiaException('Ese periodo ya esta programado para el tema.',409);throw $e;}
        return $id;
    }

    public function eliminarProgramacion(int $id): void
    {
        $fila=$this->fila('SELECT id_tema FROM plan_tema_programacion WHERE id_programacion=:id',[':id'=>$id]);
        if(!$fila)throw new PedagogiaException('Programacion no encontrada.',404);
        $this->planConAcceso($this->planDeElemento('tema',(int)$fila['id_tema']),true);
        $this->db->prepare('DELETE FROM plan_tema_programacion WHERE id_programacion=:id')->execute([':id'=>$id]);
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
            'unidad'=>['table'=>'plan_unidades','pk'=>'id_unidad','parent'=>'id_plan','fields'=>['nombre'=>'short','descripcion'=>'nullable','horas_catedra'=>'number','proceso_inicio'=>'date','proceso_fin'=>'date','area_transversal'=>'nullable','metodologia'=>'nullable','medios_verificacion'=>'nullable']],
            'capacidad'=>['table'=>'plan_capacidades','pk'=>'id_capacidad','parent'=>'id_unidad','fields'=>['descripcion'=>'required','proceso_desarrollo'=>'nullable']],
            'tema'=>['table'=>'plan_temas','pk'=>'id_tema','parent'=>'id_capacidad','fields'=>['titulo'=>'short','contenido'=>'nullable','horas_catedra'=>'number']],
            'indicador'=>['table'=>'plan_indicadores','pk'=>'id_indicador','parent'=>'id_tema','fields'=>['descripcion'=>'required']],
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
    private function siguienteOrden(string $tabla,string $padre,int $id): int{$s=$this->db->prepare("SELECT COALESCE(MAX(orden),0)+1 FROM $tabla WHERE $padre=:id");$s->execute([':id'=>$id]);return(int)$s->fetchColumn();}
    private function porIds(string $sql,array $ids): array{if(!$ids)return[];$m=implode(',',array_fill(0,count($ids),'?'));$s=$this->db->prepare(sprintf($sql,$m));$s->execute(array_values($ids));return$s->fetchAll(PDO::FETCH_ASSOC);}
    private function filas(string $sql,array $p=[]): array{$s=$this->db->prepare($sql);$s->execute($p);return$s->fetchAll(PDO::FETCH_ASSOC);}
    private function fila(string $sql,array $p=[]): ?array{$s=$this->db->prepare($sql);$s->execute($p);$x=$s->fetch(PDO::FETCH_ASSOC);return$x?:null;}
    private function id(mixed $v,string $n): int{$x=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$x)throw new PedagogiaException("$n no valido.");return(int)$x;}
    private function texto(mixed $v,string $n,int $max): string{$x=trim((string)$v);if($x===''||mb_strlen($x)>$max)throw new PedagogiaException("$n es obligatorio o demasiado extenso.");return$x;}
    private function nullable(mixed $v): ?string{$x=trim((string)$v);return$x===''?null:$x;}
    private function fecha(mixed $v,string $n): string{$x=(string)$v;$d=DateTimeImmutable::createFromFormat('!Y-m-d',$x);if(!$d||$d->format('Y-m-d')!==$x)throw new PedagogiaException("$n no valida.");return$x;}
    private function fechaNullable(mixed $v): ?string{return($v===''||$v===null)?null:$this->fecha($v,'Fecha');}
}
?>
