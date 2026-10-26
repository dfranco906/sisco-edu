@echo off
chcp 65001 > nul
cls
echo ============================================================================
echo   SISCO-EDU: CONFIGURACIÓN DETERMINÍSTICA PARA MODO OFFLINE (NUEVA MÁQUINA)
echo ============================================================================
echo.

set PHP_EXE=C:\xampp\php\php.exe
if not exist "%PHP_EXE%" (
    set PHP_EXE=php
)

echo [1/3] Verificando archivo de configuración local...
if not exist "config.local.php" (
    echo Creando config.local.php a partir de config.local.example.php...
    copy config.local.example.php config.local.php > nul
)
echo [OK] config.local.php verificado.
echo.

echo [2/3] Cambiando entorno a MODO LOCAL OFFLINE...
"%PHP_EXE%" scripts/switch_env.php local
echo.

echo [3/3] Inicializando esquema y datos semilla en la base local...
"%PHP_EXE%" scripts/import_seed_local.php

if %ERRORLEVEL% equ 0 (
    echo.
    echo ============================================================================
    echo [EXITO] ¡Entorno local offline configurado y listo para pruebas!
    echo Ya puedes realizar pruebas de huellas, asistencia y navegación sin Internet.
    echo ============================================================================
) else (
    echo.
    echo ============================================================================
    echo [AVISO] Asegúrate de que tu PostgreSQL o Supabase Local esté corriendo.
    echo - Si usas Supabase CLI: abre una terminal y ejecuta 'supabase start'.
    echo - Si usas PostgreSQL de Windows: inicia el servicio PostgreSQL en el puerto indicado.
    echo ============================================================================
)

echo.
pause
