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

"%PHP_EXE%" -r "require 'src/classes/SincronizadorNube.php'; try { $s = new SincronizadorNube(); echo 'Conectando a bases de datos...' . PHP_EOL; $res = $s->sincronizarLocalHaciaNube(); echo PHP_EOL . '>>> SINCRONIZACIÓN EXITOSA <<<' . PHP_EOL; foreach($res['resumen'] as $t => $cant) { if($cant > 0) echo '  - ' . str_pad($t, 25) . ': ' . $cant . ' registros' . PHP_EOL; } echo PHP_EOL . 'Detalles:' . PHP_EOL; foreach($res['detalles'] as $d) echo '  ' . $d . PHP_EOL; } catch(Throwable $e) { echo PHP_EOL . 'ERROR: ' . $e->getMessage() . PHP_EOL; exit(1); }"

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
