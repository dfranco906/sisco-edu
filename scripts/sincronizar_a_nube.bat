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

set PHP_EXE=php
where php >nul 2>&1
if %ERRORLEVEL% neq 0 (
    set "PHP_EXE="
    for /d %%D in (C:\laragon\bin\php\php-*) do if exist "%%D\php.exe" set "PHP_EXE=%%D\php.exe"
    if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
    if not defined PHP_EXE set "PHP_EXE=php"
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
