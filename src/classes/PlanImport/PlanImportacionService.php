<?php
declare(strict_types=1);
namespace SiscoEdu\PlanImport;

final class PlanImportacionService
{
    public function __construct(private readonly \PDO $db, private readonly array $user, private readonly array $config) {}

    public function upload(array $file, int $assignment, int $year): array
    {
        $context = \asegurarAccesoAsignacion($this->db, $this->user, $assignment, true);
        if ($year !== (int)$context['anio_lectivo']) throw new PlanImportException('El año debe coincidir con la asignación.', 'ASSIGNMENT_YEAR_MISMATCH');
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_string($file['tmp_name'] ?? null) || !is_uploaded_file($file['tmp_name'])) throw new PlanImportException('La carga del PDF no se completó.', 'INVALID_UPLOAD');
        if (!is_string($file['name'] ?? null) || strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') throw new PlanImportException('Seleccione un archivo PDF.', 'INVALID_EXTENSION');
        $size = filesize($file['tmp_name']);
        if (!$size || $size > $this->config['limits']['max_file_bytes']) throw new PlanImportException('El PDF debe tener contenido y no superar 10 MB.', 'INVALID_FILE_SIZE', 2, 413);
        if (file_get_contents($file['tmp_name'], false, null, 0, 5) !== '%PDF-') throw new PlanImportException('La firma del archivo no corresponde a un PDF.', 'INVALID_PDF_SIGNATURE');
        if (!in_array((new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), $this->config['allowed_mime_types'], true)) throw new PlanImportException('El archivo no contiene un PDF válido.', 'INVALID_MIME');
        $staging = new PlanImportStaging($this->config);
        $token = $staging->create($file['tmp_name'], $this->user['id_usuario'], $assignment, $year, $file['name'], true);
        try {
            $result = $staging->withToken($token, $this->user['id_usuario'], function (array &$state, string $pdf) use ($context): array {
                $state['plan'] = PdfPlanImporter::fromConfig($this->config)->importNamed($pdf, $state['filename'], true);
                $state['parser_warnings'] = $state['plan']['warnings'];
                $state['context'] = $context;
                return $this->previewData($state);
            }, true);
            return ['token'=>$token]+$result;
        } catch (\Throwable $e) {
            $staging->delete($token, $this->user['id_usuario']);
            throw $e;
        }
    }

    public function previewData(array $state): array
    {
        $existing = $this->db->prepare('SELECT id_plan FROM planes_anuales WHERE id_asignacion=? AND anio=?');
        $existing->execute([$state['assignment'], $state['year']]);
        $id = $existing->fetchColumn();
        $catalogs=$this->catalogs();
        return [
            'plan'=>$state['plan'], 'assignment'=>$state['assignment'], 'year'=>$state['year'],
            'expires'=>$state['expires'], 'decisions'=>$state['decisions'], 'programming'=>$state['programming'],
            'existing_plan'=>$id ? (int)$id : null,
            'context'=>$state['context'] ?? [],
            'catalogs'=>$catalogs,
            'suggestions'=>(new CatalogMatcher())->suggestions($state['plan'],$catalogs),
            'import_warnings'=>$id ? ['Ya existe un plan para esta asignación y año. No se puede importar ni sobrescribirlo.'] : [],
        ];
    }

    public function catalogs(): array
    {
        return [
            'procedimientos'=>$this->db->query('SELECT id_procedimiento id,nombre FROM procedimientos_evaluativos WHERE activo=1 ORDER BY nombre')->fetchAll(\PDO::FETCH_ASSOC),
            'instrumentos'=>$this->db->query('SELECT id_instrumento id,nombre FROM instrumentos_evaluativos WHERE activo=1 ORDER BY nombre')->fetchAll(\PDO::FETCH_ASSOC),
        ];
    }
}
