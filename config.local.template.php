<?php
// config.local.template.php (SISCO-EDU: Plantilla de Configuración Local y Cloud)
// Copiar este archivo como config.local.php para configurar el entorno.

// Modo de base de datos activo: 'local' (offline para pruebas) o 'cloud' (online Supabase)
$modoActivo = 'local';

// Perfil Supabase Cloud (Producción)
$configCloud = [
    'DB_DRIVER' => 'pgsql',
    'DB_HOST'   => 'aws-0-us-east-1.pooler.supabase.com',
    'DB_PORT'   => 6543,
    'DB_NAME'   => 'postgres',
    'DB_USER'   => 'postgres.eiwhpbwzaelyuwxmpdss',
    'DB_PASS'   => 'R%p2Bx0G&o$G0g',
    'DB_SSL'    => true,
];

// Perfil Local Offline: Supabase CLI (puerto 54322) o PostgreSQL Nativo (puerto 5432)
$configLocal = [
    'DB_DRIVER' => 'pgsql',
    'DB_HOST'   => '127.0.0.1',
    'DB_PORT'   => 54322, // 54322 para Supabase CLI, o 5432 para PostgreSQL nativo
    'DB_NAME'   => 'postgres',
    'DB_USER'   => 'postgres',
    'DB_PASS'   => 'postgres',
    'DB_SSL'    => false,
];

$dbActual = ($modoActivo === 'local') ? $configLocal : $configCloud;

return array_merge([
    'MODO_ENTORNO'         => $modoActivo,
    
    // Servidor y Dispositivos IoT (Ajustar según la red del aula o laboratorio)
    'IP_SERVIDOR'          => '192.168.100.52',
    'IP_ESP32_REGISTRADOR' => '192.168.100.39',
    'IP_ESP32_GATEWAY'     => '192.168.100.11',
    'GATEWAY_SYNC_URL'     => 'http://192.168.100.11/sync',
    'GATEWAY_API_KEY'      => 'SISCO_GATEWAY_2026_SECRETO',

    // Perfiles persistentes para el Sincronizador de 1 Clic
    'CLOUD_DB'             => $configCloud,
    'LOCAL_DB'             => $configLocal,

    // Supabase Realtime (WebSockets)
    'SUPABASE_URL'         => 'https://eiwhpbwzaelyuwxmpdss.supabase.co',
    'SUPABASE_ANON_KEY'    => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImVpd2hwYnd6YWVseXV3eG1wZHNzIiwicm9sZSI6ImFub24iLCJpYXQiOjE3OTI0MTA5MDksImV4cCI6MjEwNzk4NjkwOX0.yI9i92CPZjc7IgKvRsC5NMX150k2vAyzmxubqB4q1-s',
], $dbActual);
