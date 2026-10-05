<?php
require_once __DIR__.'/app.php';

function deviceHeader(string $name): string
{
    foreach (function_exists('getallheaders') ? getallheaders() : [] as $key => $value) {
        if (strcasecmp($key, $name) === 0) return (string)$value;
    }
    return (string)($_SERVER['HTTP_'.strtoupper(str_replace('-', '_', $name))] ?? '');
}

function deviceAuthenticated(): bool
{
    return GATEWAY_API_KEY !== '' && hash_equals(GATEWAY_API_KEY, deviceHeader('X-GATEWAY-KEY'));
}

function requireDevice(string $method): void
{
    header('Content-Type: application/json; charset=UTF-8');
    if (!deviceAuthenticated()) {
        http_response_code(404); echo json_encode(['ok'=>false,'status'=>'not_found']); exit;
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        http_response_code(405); echo json_encode(['ok'=>false,'status'=>'method_not_allowed']); exit;
    }
}
