$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot

php (Join-Path $PSScriptRoot 'build-blueprint-html.php')

if ($LASTEXITCODE -ne 0) {
    throw 'Gagal membangun HTML blueprint.'
}

php (Join-Path $PSScriptRoot 'build-blueprint-docx.php')

if ($LASTEXITCODE -ne 0) {
    throw 'Gagal membangun DOCX blueprint.'
}

powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'export-blueprint-pdf.ps1')

Write-Output "Selesai: $root\Blueprint-Sistem-Notulensi.pdf"
