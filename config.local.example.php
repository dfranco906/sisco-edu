<?php
// config.local.example.php (SISCO-EDU: Plantilla de Configuración Local y Cloud)
// Copia este archivo como 'config.local.php' en la raíz y completa tus credenciales.
// 'config.local.php' está ignorado por Git para proteger tus claves y contraseñas.

// Modo de base de datos activo: 'local' (offline para pruebas locales) o 'cloud' (online Supabase)
$modoActivo = 'local';

// Perfil Supabase Cloud (Producción / Remoto)
$configCloud = [
    'DB_DRIVER' => 'pgsql',
    'DB_HOST'   => 'aws-0-us-east-1.pooler.supabase.com',
    'DB_PORT'   => 6543,
    'DB_NAME'   => 'postgres',
    'DB_USER'   => 'postgres.tu_project_ref', // Reemplazar con el usuario pooler de tu proyecto Supabase
    'DB_PASS'   => 'TU_PASSWORD_DE_SUPABASE', // Reemplazar con la contraseña de tu base de datos
    'DB_SSL'    => true,
];

// Perfil Local Offline: PostgreSQL Nativo (puerto 5432) o Supabase CLI (puerto 54322)
$configLocal = [
    'DB_DRIVER' => 'pgsql',
    'DB_HOST'   => '127.0.0.1',
    'DB_PORT'   => 5432, // 5432 para PostgreSQL nativo o 54322 para Supabase CLI
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
    'GATEWAY_API_KEY'      => 'TU_CLAVE_SECRETA_GATEWAY',

    // Perfiles persistentes para el Sincronizador de 1 Clic
    'CLOUD_DB'             => $configCloud,
    'LOCAL_DB'             => $configLocal,

    // Supabase Realtime (WebSockets)
    'SUPABASE_URL'         => 'https://tu_project_ref.supabase.co',
    'SUPABASE_ANON_KEY'    => 'TU_SUPABASE_ANON_KEY',
], $dbActual);
