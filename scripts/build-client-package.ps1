$ErrorActionPreference = 'Stop'

$root = Split-Path -Parent $PSScriptRoot

php (Join-Path $PSScriptRoot 'build-client-package-markdown.php')

if ($LASTEXITCODE -ne 0) {
    throw 'Gagal membangun Markdown paket klien.'
}

php (Join-Path $PSScriptRoot 'build-client-package-html.php')

if ($LASTEXITCODE -ne 0) {
    throw 'Gagal membangun HTML paket klien.'
}

php (Join-Path $PSScriptRoot 'build-client-package-docx.php')

if ($LASTEXITCODE -ne 0) {
    throw 'Gagal membangun DOCX paket klien.'
}

powershell -NoProfile -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'export-client-package-pdf.ps1')

Write-Output "Selesai: $root\Blueprint-PRD-ERD-Sistem-Notulensi.pdf"
