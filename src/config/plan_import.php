<?php
declare(strict_types=1);

$planImportProjectRoot = dirname(__DIR__, 2);
$planImportStorage = trim((string) (getenv('SISCO_PLAN_IMPORT_STORAGE') ?: ''));
if ($planImportStorage === '') {
    $planImportStorage = rtrim(sys_get_temp_dir(), '\\/').DIRECTORY_SEPARATOR.'sisco-edu-plan-import';
}

return [
    'pdftotext_binary' => trim((string) (getenv('SISCO_PDFTOTEXT_PATH') ?: '')),
    'storage_path' => $planImportStorage,
    'document_root' => realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: $planImportProjectRoot,
    'limits' => [
        'max_file_bytes' => 10 * 1024 * 1024,
        'max_pages' => 100,
        'timeout_seconds' => 15,
        'max_output_bytes' => 5 * 1024 * 1024,
        'max_preview_json_bytes' => 5 * 1024 * 1024,
        'staging_ttl_seconds' => 2 * 60 * 60,
    ],
    'allowed_mime_types' => ['application/pdf', 'application/x-pdf'],
    'preflight_sample_pdf' => $planImportProjectRoot.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR.'planes'.DIRECTORY_SEPARATOR.'Plan Anual - tercer curso - BTI - Administracion financiera.pdf',
];
