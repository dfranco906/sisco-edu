param(
    [Parameter(Mandatory = $true)]
    [string]$SourceIp,

    [Parameter(Mandatory = $true)]
    [string]$DestinationIp,

    [switch]$AllowOverwrite
)

$ErrorActionPreference = 'Stop'

$sourceUrl = "http://$SourceIp/obtener_template"
$destinationUrl = "http://$DestinationIp/importar_template"
if ($AllowOverwrite) {
    $destinationUrl += '?allow_overwrite=1'
}

Write-Host "Leyendo template puro desde $sourceUrl"
$source = Invoke-RestMethod -Method Get -Uri $sourceUrl -TimeoutSec 15

if ($source.status -ne 'success') {
    throw "El sensor origen devolvió estado '$($source.status)'"
}

$template = [string]$source.template
if ($source.bytes -ne 1408 -or $template.Length -ne 2816) {
    throw "Longitud inválida: bytes=$($source.bytes), chars=$($template.Length)"
}
if ($template -notmatch '^[0-9A-Fa-f]{2816}$') {
    throw 'El template exportado no es HEX puro de 2816 caracteres'
}

Write-Host "Importando en slot 1 de $DestinationIp (CRC32 origen: $($source.crc32))"
$destination = Invoke-RestMethod `
    -Method Post `
    -Uri $destinationUrl `
    -ContentType 'text/plain' `
    -Body $template `
    -TimeoutSec 30

if ($destination.status -ne 'success' -or !$destination.readback_verified) {
    throw "La importación no fue verificada: $($destination | ConvertTo-Json -Compress)"
}
if ([string]$destination.crc32 -ne [string]$source.crc32) {
    throw "CRC32 distinto: origen=$($source.crc32), destino=$($destination.crc32)"
}

Write-Host "Readback verificado. Slot=$($destination.slot_local), CRC32=$($destination.crc32)"
Write-Host 'Paso manual pendiente: colocar el mismo dedo en el DY50 destino y verificar RECONOCIMIENTO OK por Serial.'
