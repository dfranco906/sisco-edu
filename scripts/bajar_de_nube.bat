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

"%PHP_EXE%" -r "require 'src/classes/SincronizadorNube.php'; try { $s = new SincronizadorNube(); echo 'Conectando...' . PHP_EOL; $res = $s->sincronizarNubeHaciaLocal(); echo PHP_EOL . '>>> DESCARGA EXITOSA <<<' . PHP_EOL; foreach($res['resumen'] as $t => $cant) { if($cant > 0) echo '  - ' . str_pad($t, 25) . ': ' . $cant . ' registros' . PHP_EOL; } } catch(Throwable $e) { echo PHP_EOL . 'ERROR: ' . $e->getMessage() . PHP_EOL; exit(1); }"

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
