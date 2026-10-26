@echo off
chcp 65001 > nul
cls
echo ============================================================================
echo   SISCO-EDU: SINCRONIZACIÓN DE BASE DE DATOS LOCAL CON SUPABASE CLOUD
echo ============================================================================
echo.
echo Conectando con BD Local y Cloud para enviar datos generados...
echo Por favor espere...
echo.

set PHP_EXE=C:\xampp\php\php.exe
if not exist "%PHP_EXE%" (
    set PHP_EXE=php
)

"%PHP_EXE%" "%~dp0sincronizar_nube_cli.php"

if %ERRORLEVEL% equ 0 (
    echo.
    echo ============================================================================
    echo [EXITO] Sincronización completada. Todos los datos locales están en la nube.
    echo ============================================================================
) else (
    echo.
    echo ============================================================================
    echo [ERROR] No se pudo completar la sincronización. Verifica tu conexión a Internet.
    echo ============================================================================
)

echo.
pause
