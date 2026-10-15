<?php
require_once __DIR__ . '/../../src/config/app.php';
$url = base_url('src/api/Huella/esp32_confirmar_huella.php');

$data = [
    "id_solicitud" => 1,
    "id_estudiante" => 5,
    "huella_id" => 99
];

$options = [
    "http" => [
        "header" => "Content-type: application/x-www-form-urlencoded\r\n",
        "method" => "POST",
        "content" => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo $result;
?>