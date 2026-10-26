@echo off
chcp 65001 > nul
set PHP_EXE=C:\xampp\php\php.exe
if not exist "%PHP_EXE%" (
    set PHP_EXE=php
)

if "%1"=="" (
    cls
    echo ============================================================================
    echo   SISCO-EDU: SELECTOR DE MODO DE BASE DE DATOS (LOCAL vs CLOUD)
    echo ============================================================================
    echo.
    "%PHP_EXE%" scripts/switch_env.php
    echo.
    echo Opciones:
    echo   [1] Cambiar a MODO LOCAL (Offline en 127.0.0.1)
    echo   [2] Cambiar a MODO CLOUD (Online en Supabase Cloud)
    echo   [3] Cancelar
    echo.
    set /p opcion="Seleccione una opción [1-3]: "
    if "%opcion%"=="1" (
        "%PHP_EXE%" scripts/switch_env.php local
    )
    if "%opcion%"=="2" (
        "%PHP_EXE%" scripts/switch_env.php cloud
    )
    pause
    exit /b 0
)

"%PHP_EXE%" scripts/switch_env.php %1
pause
