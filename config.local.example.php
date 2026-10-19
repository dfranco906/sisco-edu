<?php
/**
 * Plantilla de configuración local para SISCO-EDU.
 *
 * Para utilizarla:
 * 1. Copia este archivo como `config.local.php` en la raíz del proyecto.
 * 2. Modifica los valores con las IPs correspondientes a tu red de prueba o producción.
 * 3. `config.local.php` ya está incluido en `.gitignore` para no sobreescribir el trabajo de tus compañeros.
 */

return [
    // IP o dominio de la computadora que corre el servidor Apache/XAMPP
    'IP_SERVIDOR'          => '192.168.100.109',

    // IP asignada al ESP32 que captura las huellas (Nodo Registrador)
    'IP_ESP32_REGISTRADOR' => '192.168.100.125',

    // IP asignada al ESP32 que actúa de Gateway LoRa
    'IP_ESP32_GATEWAY'     => '192.168.100.17',

    // URL completa para el webhook de sincronización del Gateway
    'GATEWAY_SYNC_URL'     => 'http://192.168.100.17/sync',

    // Clave secreta enviada en la cabecera X-GATEWAY-KEY
    'GATEWAY_API_KEY'      => 'SISCO_GATEWAY_2026_SECRETO',

    // --- Credenciales de Base de Datos ---
    // Opción 1: MySQL Local (XAMPP por defecto)
    'DB_DRIVER'            => 'mysql',
    'DB_HOST'              => 'localhost',
    'DB_PORT'              => 3306,
    'DB_NAME'              => 'sisco_db',
    'DB_USER'              => 'root',
    'DB_PASS'              => '',
    'DB_SSL'               => false,

    // Opción 2: Supabase PostgreSQL (Cloud)
    // 'DB_DRIVER'            => 'pgsql',
    // 'DB_HOST'              => 'aws-0-us-east-1.pooler.supabase.com', // Reemplazar con el pooler de tu región
    // 'DB_PORT'              => 6543,                                  // Puerto del Transaction Pooler (IPv4)
    // 'DB_NAME'              => 'postgres',
    // 'DB_USER'              => 'postgres.tu_project_ref',
    // 'DB_PASS'              => 'TU_PASSWORD_DE_SUPABASE',
    // 'DB_SSL'               => true,
];
