@echo off
chcp 65001 > nul
cls
echo ============================================================================
echo   SISCO-EDU: INSTALADOR AUTOMÁTICO DE SUPABASE CLI PARA WINDOWS
echo ============================================================================
echo.
echo Descargando la última versión de Supabase CLI para Windows (x64)...
echo.

if not exist "bin" mkdir "bin"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference = 'Stop';" ^
    "Write-Host 'Consultando release oficial de Supabase CLI en GitHub...';" ^
    "$rel = Invoke-RestMethod -Uri 'https://api.github.com/repos/supabase/cli/releases/latest';" ^
    "$asset = $rel.assets | Where-Object { $_.name -like '*windows_amd64.zip' } | Select-Object -First 1;" ^
    "if (-not $asset) { throw 'No se encontró el ejecutable de Windows en la release.'; }" ^
    "$zipPath = Join-Path $PWD 'bin\supabase.zip';" ^
    "Write-Host ('Descargando ' + $asset.name + ' (' + [math]::Round($asset.size/1MB, 1) + ' MB)...');" ^
    "Invoke-WebRequest -Uri $asset.browser_download_url -OutFile $zipPath;" ^
    "Write-Host 'Descomprimiendo en bin\supabase.exe...';" ^
    "Expand-Archive -Path $zipPath -DestinationPath (Join-Path $PWD 'bin') -Force;" ^
    "Remove-Item -Path $zipPath -Force;" ^
    "Write-Host '[OK] Supabase CLI instalado correctamente en bin\supabase.exe' -ForegroundColor Green;"

if %ERRORLEVEL% equ 0 (
    echo.
    echo ============================================================================
    echo [EXITO] Supabase CLI instalado en: %CD%\bin\supabase.exe
    echo.
    echo Para iniciar el entorno local:
    echo   1. Asegúrate de tener Docker Desktop o Docker corriendo.
    echo   2. Ejecuta: bin\supabase.exe start
    echo ============================================================================
) else (
    echo.
    echo [ERROR] No se pudo descargar Supabase CLI. Puedes instalarlo con Scoop:
    echo   scoop bucket add supabase https://github.com/supabase/scoop-bucket.git
    echo   scoop install supabase
)

echo.
pause
