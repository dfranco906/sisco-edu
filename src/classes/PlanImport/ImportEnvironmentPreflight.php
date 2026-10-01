<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

use SiscoEdu\PlanImport\Pdf\XpdfTableExtractor;

/** Runs in the serving PHP process. All database operations are SELECTs. */
final class ImportEnvironmentPreflight
{
    private const SCHEMA = [
        'usuarios'=>['id_usuario','rol','activo'],
        'profesores'=>['id_profesor','id_usuario','nombre','apellido','activo'],
        'asignacion_docente'=>['id_asignacion','id_profesor','id_materia','id_grado','carga_horaria','anio_lectivo','activo'],
        'materias'=>['id_materia','nombre','activo'], 'grados'=>['id_grado','id_aula','nombre','activo'], 'aulas'=>['id_aula','nombre','activo'],
        'planes_anuales'=>['id_plan','id_asignacion','anio','competencia_general','competencia_especifica','institucion_fuente','materia_fuente','profesor_fuente','curso_fuente','turno_fuente','anio_fuente','dias_clase_fuente','importacion_origen_json','estado','created_by'],
        'plan_unidades'=>['id_unidad','id_plan','codigo','nombre','descripcion','orden','horas_catedra','tiempo_texto','proceso_texto','area_transversal','metodologia','medios_verificacion'],
        'plan_capacidades'=>['id_capacidad','id_unidad','descripcion','proceso_desarrollo','orden'],
        'plan_temas'=>['id_tema','id_capacidad','codigo','titulo','contenido','horas_catedra','tiempo_texto','fecha_texto','orden'],
        'plan_indicadores'=>['id_indicador','id_tema','codigo','descripcion','orden'],
        'procedimientos_evaluativos'=>['id_procedimiento','nombre','activo'],
        'instrumentos_evaluativos'=>['id_instrumento','nombre','activo'],
        'plan_tema_procedimientos'=>['id_tema','id_procedimiento','texto_fuente','orden'],
        'plan_tema_instrumentos'=>['id_tema','id_instrumento','texto_fuente','orden'],
        'plan_tema_programacion'=>['id_tema','fecha_inicio','fecha_fin','horas_catedra_planificadas','observaciones','orden'],
    ];

    public function __construct(private readonly \PDO $db, private readonly array $user, private readonly array $config) {}

    public function check(): array
    {
        $checks=[]; $assignmentCount=0;
        $this->probe($checks, 'usuario', function (): void {
            if (\validarIdentidadSesion($this->db, $this->user)===null) throw new PlanImportException('Sesion no valida.', 'INVALID_SESSION', 6, 401);
        });
        $this->probe($checks, 'schema', fn()=>$this->checkSchema());
        $this->probe($checks, 'asignaciones', function () use (&$assignmentCount): void {
            $assignmentCount=count((new \PlanificacionPedagogica($this->db, $this->user))->asignacionesDisponibles());
            if ($assignmentCount===0) throw new PlanImportException('No tiene asignaciones activas disponibles.', 'NO_ASSIGNMENTS', 6, 409);
        });
        $this->probe($checks, 'limites', fn()=>$this->checkLimits());
        $this->probe($checks, 'staging', fn()=>(new PlanImportStaging($this->config))->checkWritable());
        $this->probe($checks, 'pdftotext', function (): void {
            $binary=(string)($this->config['pdftotext_binary']??'');
            if ($binary==='' || !is_file($binary) || !is_executable($binary)) throw new PlanImportException('Extractor PDF no disponible.', 'EXTRACTOR_NOT_FOUND', 4, 503);
            // A real extraction also verifies launch permission and support for -table.
            (new XpdfTableExtractor($this->config))->extract((string)$this->config['preflight_sample_pdf']);
        });
        return [
            'ready'=>!in_array('FAIL', array_column($checks, 'status'), true),
            'checks'=>$checks, 'available_assignments'=>$assignmentCount,
            'limits'=>array_intersect_key($this->config['limits']??[], array_flip(['max_file_bytes','max_pages','timeout_seconds','max_preview_json_bytes','staging_ttl_seconds'])),
            'runtime'=>PHP_SAPI,
        ];
    }

    private function probe(array &$checks, string $name, callable $probe): void
    {
        try { $probe(); $checks[$name]=['status'=>'PASS']; }
        catch (\Throwable $error) {
            $type=$error instanceof PlanImportException ? $error->errorType : 'ENVIRONMENT_CHECK_FAILED';
            error_log('plan_import_preflight: '.$name.' '.$type);
            $checks[$name]=['status'=>'FAIL','error_type'=>$type];
        }
    }

    private function checkSchema(): void
    {
        $query=$this->db->prepare('SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('.implode(',',array_fill(0,count(self::SCHEMA),'?')).')');
        $query->execute(array_keys(self::SCHEMA)); $found=[];
        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $row) $found[$row['TABLE_NAME']][]=$row['COLUMN_NAME'];
        foreach (self::SCHEMA as $table=>$columns) {
            if (array_diff($columns,$found[$table]??[])) throw new PlanImportException('El schema del importador requiere actualizacion.', 'SCHEMA_NOT_READY', 6, 503);
        }
    }

    private function checkLimits(): void
    {
        foreach (['max_file_bytes','max_pages','timeout_seconds','max_output_bytes','max_preview_json_bytes','staging_ttl_seconds'] as $key) {
            $value=$this->config['limits'][$key]??null;
            if ((!is_int($value) && !is_float($value)) || $value<=0 || !is_finite((float)$value)) throw new PlanImportException('Limites no configurados.', 'IMPORT_LIMITS_INVALID', 4, 503);
        }
        $fileLimit=(int)$this->config['limits']['max_file_bytes'];
        $postLimit=$this->iniBytes((string)ini_get('post_max_size'));
        $execution=(int)ini_get('max_execution_time');
        if (!filter_var(ini_get('file_uploads'),FILTER_VALIDATE_BOOLEAN)
            || $this->iniBytes((string)ini_get('upload_max_filesize'))<$fileLimit
            || ($postLimit>0 && $postLimit<$fileLimit+1024*1024)
            || ($execution>0 && $execution<=(float)$this->config['limits']['timeout_seconds'])
            || !function_exists('proc_open') || !extension_loaded('fileinfo') || !extension_loaded('mbstring')) {
            throw new PlanImportException('PHP no cumple los requisitos del importador.', 'PHP_IMPORT_LIMITS_INVALID', 4, 503);
        }
    }

    private function iniBytes(string $value): int
    {
        $value=trim($value);
        $multiplier=match(strtolower(substr($value,-1))) { 'g'=>1024**3,'m'=>1024**2,'k'=>1024,default=>1 };
        return (int)((float)$value*$multiplier);
    }
}
