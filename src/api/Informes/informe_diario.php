<?php
require_once __DIR__ . '/../../config/api_auth.php';require_once __DIR__ . '/../../classes/ClaseDiaria.php';
requerirMetodo(['GET','POST']);$usuario=usuarioActual(['SuperAdmin','Administracion','Coordinador','Profesor']);
try{$db=(new Database())->getConnection();$s=new ClaseDiaria($db);
 if($_SERVER['REQUEST_METHOD']==='GET'){
   if(!empty($_GET['id_clase'])){$id=enteroPositivo($_GET['id_clase'],'Clase');$base=$db->prepare('SELECT id_asignacion FROM clases_diarias WHERE id_clase=:id');$base->execute([':id'=>$id]);$as=(int)$base->fetchColumn();if(!$as)throw new PedagogiaException('Clase no encontrada.',404);asegurarAccesoAsignacion($db,$usuario,$as,false);$data=$s->informe($id);$cfg=$db->query("SELECT membrete_path FROM configuracion_informes WHERE activo=1 ORDER BY updated_at DESC,id_configuracion DESC LIMIT 1")->fetchColumn();$data['membrete_path']=$cfg?:null;responderJson(['success'=>true,'status'=>'success','data'=>$data]);}
   $f=$_GET;if($usuario['rol']==='Profesor'){$pid=profesorDeUsuario($db,$usuario);if(!$pid)throw new PedagogiaException('La cuenta no esta vinculada a un profesor activo.',403);$f['id_profesor']=$pid;}$data=$s->buscarClases($f);responderJson(['success'=>true,'status'=>'success','data'=>$data]);
 }
 $d=entradaJson();$accion=(string)($d['accion']??'asignar_contenido');if($accion!=='asignar_contenido')throw new PedagogiaException('Accion no valida.');$idClase=enteroPositivo($d['id_clase']??null,'Clase');$q=$db->prepare('SELECT id_asignacion FROM clases_diarias WHERE id_clase=:id');$q->execute([':id'=>$idClase]);$as=(int)$q->fetchColumn();if(!$as)throw new PedagogiaException('Clase no encontrada.',404);asegurarAccesoAsignacion($db,$usuario,$as,true);$s->asignarTema($idClase,enteroPositivo($d['id_tema']??null,'Tema'));responderJson(['success'=>true,'status'=>'success','message'=>'Contenido asignado a la clase.']);
}catch(PedagogiaException $e){responderJson(['success'=>false,'status'=>'error','message'=>$e->getMessage()],$e->http);}catch(Throwable $e){error_log('informe_diario: '.$e->getMessage());responderJson(['success'=>false,'status'=>'error','message'=>'No se pudo generar el informe.'],500);}
?>
