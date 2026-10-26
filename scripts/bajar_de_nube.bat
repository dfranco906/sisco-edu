@echo off
chcp 65001 > nul
cls
echo ============================================================================
echo   SISCO-EDU: DESCARGAR Y ACTUALIZAR BASE LOCAL DESDE SUPABASE CLOUD
echo ============================================================================
echo.
echo Descargando datos desde la nube hacia la base de datos local...
echo.

set PHP_EXE=C:\xampp\php\php.exe
if not exist "%PHP_EXE%" (
    set PHP_EXE=php
)

"%PHP_EXE%" "%~dp0bajar_nube_cli.php"

if %ERRORLEVEL% equ 0 (
    echo.
    echo ============================================================================
    echo [EXITO] Base de datos local actualizada con los últimos datos de la nube.
    echo ============================================================================
) else (
    echo.
    echo ============================================================================
    echo [ERROR] No se pudo descargar desde la nube. Verifica tu conexión.
    echo ============================================================================
)

echo.
pause
