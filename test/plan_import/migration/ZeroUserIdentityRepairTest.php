<?php
declare(strict_types=1);
require_once __DIR__.'/../support/TestEnvironment.php';
require_once dirname(__DIR__,3).'/src/classes/ZeroUserIdentityRepair.php';
require_once dirname(__DIR__,3).'/src/config/session_identity.php';
$env=new PlanImportTestEnvironment();
try {
    $env->db->exec("SET SESSION sql_mode=CONCAT(@@sql_mode,',NO_AUTO_VALUE_ON_ZERO')");
    $hash=password_hash('TemporaryRepairTest',PASSWORD_DEFAULT);
    $q=$env->db->prepare("INSERT INTO usuarios (id_usuario,nombre,apellido,usuario,email,celular,password,rol,activo) VALUES (0,'Zero','Admin','repair_admin','repair@test.local',981234569,?,'SuperAdmin',1)");$q->execute([$hash]);
    $env->db->exec('CREATE TABLE identity_repair_test (id INT PRIMARY KEY,user_id INT NOT NULL,payload TEXT,CONSTRAINT fk_identity_repair_test FOREIGN KEY(user_id) REFERENCES usuarios(id_usuario) ON UPDATE CASCADE) ENGINE=InnoDB');
    $env->db->exec("INSERT INTO identity_repair_test VALUES (1,0,'Original academic content')");
    $repair=new ZeroUserIdentityRepair($env->db);$target=$env->user+100;
    $dry=$repair->run('repair_admin',$target);
    if($dry['status']!=='dry_run'||(int)$env->db->query('SELECT user_id FROM identity_repair_test')->fetchColumn()!==0)throw new RuntimeException('Dry run mutated data');
    try {$repair->run('repair_admin',$env->user,true);throw new LogicException('Occupied target accepted');}catch(RuntimeException $error){}
    if($env->db->inTransaction())throw new RuntimeException('Failed repair left transaction open');
    $env->db->exec("CREATE TRIGGER identity_repair_extra_change AFTER UPDATE ON usuarios FOR EACH ROW UPDATE identity_repair_test SET payload='Unexpected modification' WHERE id=1");
    try {$repair->run('repair_admin',$target,true);throw new LogicException('Additional change accepted');}catch(RuntimeException $error){if(!str_contains($error->getMessage(),'Unexpected data change'))throw $error;}
    $original=$env->db->query('SELECT user_id,payload FROM identity_repair_test')->fetch(PDO::FETCH_ASSOC);
    if($env->db->inTransaction()||(int)$original['user_id']!==0||$original['payload']!=='Original academic content')throw new RuntimeException('Additional change was not rolled back');
    $env->db->exec('DROP TRIGGER identity_repair_extra_change');
    $result=$repair->run('repair_admin',$target,true);
    $row=$env->db->query('SELECT * FROM identity_repair_test')->fetch(PDO::FETCH_ASSOC);
    if($result['rows_inserted']!==0||!$result['only_identity_references_changed']||(int)$row['user_id']!==$target||$row['payload']!=='Original academic content')throw new RuntimeException('Cascade/content preservation failed');
    $saved=$env->db->query("SELECT password FROM usuarios WHERE usuario='repair_admin'")->fetchColumn();
    if($saved!==$hash||!password_verify('TemporaryRepairTest',$saved))throw new RuntimeException('Credential changed');
    if(validarIdentidadSesion($env->db,['id_usuario'=>$target,'rol'=>'SuperAdmin'])===null)throw new RuntimeException('Repaired identity remains invalid');
    if(validarIdentidadSesion($env->db,['id_usuario'=>0,'rol'=>'SuperAdmin'])!==null)throw new RuntimeException('Zero identity accepted');
    if($repair->run('repair_admin',$target,true)['status']!=='already_repaired')throw new RuntimeException('Repair not idempotent');
    echo "OK | ZeroUserIdentityRepairTest (dry run, occupied target and unexpected change rollback, cascade, content/credential preservation, valid identity, idempotency; temporary DB)\n";
} finally {$env->close();}
