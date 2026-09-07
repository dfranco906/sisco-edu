<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}

final class PlanImportTestEnvironment
{
    public PDO $db;
    public string $database;
    public string $directory;
    public string $url;
    public int $assignment;
    public int $otherAssignment;
    public int $user;
    public string $session;
    public string $csrf;
    private mixed $server = null;
    private PDO $admin;

    public function __construct()
    {
        $this->database = 'sisco_pdf_test_'.bin2hex(random_bytes(6));
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->database;
        mkdir($this->directory, 0700);
        $this->admin = new PDO('mysql:host=localhost;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $this->admin->exec("CREATE DATABASE `{$this->database}` CHARACTER SET utf8mb4");
        $this->db = new PDO('mysql:host=localhost;dbname='.$this->database.';charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $this->db->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->admin->query('SHOW TABLES FROM sisco_db')->fetchAll(PDO::FETCH_COLUMN) as $table) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/D',$table)) throw new RuntimeException('Tabla inesperada');
            $ddl = $this->admin->query("SHOW CREATE TABLE sisco_db.`$table`")->fetch(PDO::FETCH_NUM)[1];
            $this->db->exec($ddl);
        }
        $this->db->exec('SET FOREIGN_KEY_CHECKS=1');
        $root = dirname(__DIR__,3);
        foreach (['src','mvc','public'] as $folder) $this->copyTree($root.'/'.$folder, $this->directory.'/'.$folder);
        // Configuración exclusiva de la copia servida; no se modifica db.php real.
        $dbFile = $this->directory.'/src/config/db.php';
        $text = file_get_contents($dbFile);
        if (substr_count($text, '"sisco_db"') !== 1) throw new RuntimeException('Configuración DB cambió: revisar aislamiento');
        file_put_contents($dbFile, str_replace('"sisco_db"', '"'.$this->database.'"', $text));
        $this->user = $this->insert("INSERT INTO usuarios (nombre,apellido,usuario,email,celular,password,rol,activo) VALUES ('PDF','Test','pdf_test','pdf@test.local',981234567,?,'Profesor',1)",[password_hash('Temporal123', PASSWORD_DEFAULT)]);
        $prof = $this->insert("INSERT INTO profesores (nombre,apellido,cedula_identidad,user_id_global,id_usuario,activo) VALUES ('PDF','Test','99999901','PDF_TEST',?,1)",[$this->user]);
        $aula = $this->insert("INSERT INTO aulas (nombre,codigo,ubicacion) VALUES ('PDF Test','PDF_TEST','Temporal')",[]);
        $grado = $this->insert("INSERT INTO grados (nombre,id_aula,room_id) VALUES ('3° BTI',?,'PDF_TEST')",[$aula]);
        $materia = $this->insert("INSERT INTO materias (nombre,activo) VALUES ('ADMINISTRACIÓN FINANCIERA',1)",[]);
        $this->assignment = $this->insert('INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',[$prof,$materia,$grado]);
        $other = $this->insert("INSERT INTO profesores (nombre,apellido,cedula_identidad,user_id_global,activo) VALUES ('Otro','Profesor','99999902','PDF_OTHER',1)",[]);
        $this->otherAssignment = $this->insert('INSERT INTO asignacion_docente (id_profesor,id_materia,id_grado,carga_horaria,anio_lectivo,activo) VALUES (?,?,?,4,2026,1)',[$other,$materia,$grado]);
        mkdir($this->directory.'/sessions',0700);
        session_save_path($this->directory.'/sessions');
        $this->session = bin2hex(random_bytes(16));
        session_id($this->session); session_start();
        $this->csrf = bin2hex(random_bytes(32));
        $_SESSION = ['id_usuario'=>$this->user,'rol'=>'Profesor','usuario'=>'pdf_test','plan_import_csrf'=>$this->csrf,'horarios_csrf'=>$this->csrf];
        session_write_close();
        $port = random_int(20000,45000);
        $this->url = 'http://127.0.0.1:'.$port;
        $env = getenv();
        $env['SISCO_PDFTOTEXT_PATH'] = getenv('SISCO_PDFTOTEXT_PATH') ?: 'C:\\Program Files\\Git\\mingw64\\bin\\pdftotext.exe';
        $env['SISCO_PLAN_IMPORT_STORAGE'] = $this->directory.'-storage';
        $this->server = proc_open([PHP_BINARY,'-d','session.save_path='.$this->directory.'/sessions','-S','127.0.0.1:'.$port,'-t',$this->directory], [0=>['pipe','r'],1=>['file',$this->directory.'/server.log','a'],2=>['file',$this->directory.'/server.log','a']],$pipes,null,$env,['bypass_shell'=>true]);
        fclose($pipes[0]);
        for ($i=0;$i<100;$i++) { $socket=@fsockopen('127.0.0.1',$port,$errno,$errstr,0.1); if ($socket) { fclose($socket); return; } usleep(50000); }
        throw new RuntimeException('Servidor de prueba no inició');
    }

    public function request(string $endpoint, string $method='GET', mixed $body=null, bool $csrf=true, bool $auth=true): array
    {
        $curl = curl_init($this->url.'/src/api/Planificacion/'.$endpoint);
        $headers = $csrf ? ['X-CSRF-Token: '.$this->csrf] : [];
        if (is_string($body)) $headers[]='Content-Type: application/json';
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$method,CURLOPT_HTTPHEADER=>$headers,CURLOPT_TIMEOUT=>30]);
        if ($auth) curl_setopt($curl,CURLOPT_COOKIE,'PHPSESSID='.$this->session);
        if ($body!==null) curl_setopt($curl,CURLOPT_POSTFIELDS,$body);
        $raw = curl_exec($curl); $status = curl_getinfo($curl,CURLINFO_RESPONSE_CODE); curl_close($curl);
        try { $json=json_decode((string)$raw,true,128,JSON_THROW_ON_ERROR); } catch (JsonException) { throw new RuntimeException('Respuesta no JSON: '.substr((string)$raw,0,500)); }
        return ['status'=>$status,'json'=>$json];
    }
    public function insert(string $sql,array $params): int { $s=$this->db->prepare($sql);$s->execute($params);return(int)$this->db->lastInsertId(); }
    public function close(): void
    {
        if (is_resource($this->server)) { proc_terminate($this->server); proc_close($this->server); $this->server=null; }
        if (!preg_match('/^sisco_pdf_test_[a-f0-9]{12}$/D',$this->database)) throw new RuntimeException('Nombre test inválido');
        $this->admin->exec("DROP DATABASE `{$this->database}`");
        foreach ([$this->directory,$this->directory.'-storage'] as $dir) {
            if (!is_dir($dir) || dirname($dir)!==sys_get_temp_dir()) continue;
            $this->removeTree($dir);
        }
    }
    private function copyTree(string $source,string $target): void
    {
        mkdir($target,0700,true);
        foreach (new DirectoryIterator($source) as $file) {
            if ($file->isDot() || $file->isLink() || $file->getFilename()==='uploads') continue;
            $dest=$target.'/'.$file->getFilename();
            if ($file->isDir()) $this->copyTree($file->getPathname(),$dest);
            else copy($file->getPathname(),$dest);
        }
    }
    private function removeTree(string $dir): void
    {
        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->isDot()) continue;
            if ($file->isLink()) throw new RuntimeException('Enlace inesperado en test');
            if ($file->isDir()) $this->removeTree($file->getPathname()); else unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
