param(
    [string]$Html = (Join-Path $PSScriptRoot '..\Blueprint-PRD-ERD-Sistem-Notulensi.html'),
    [string]$Pdf = (Join-Path $PSScriptRoot '..\Blueprint-PRD-ERD-Sistem-Notulensi.pdf')
)

$ErrorActionPreference = 'Stop'

$chromeCandidates = @(
    'C:\Program Files\Google\Chrome\Application\chrome.exe',
    'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
)

$browser = $chromeCandidates | Where-Object { Test-Path -LiteralPath $_ } | Select-Object -First 1

if (-not $browser) {
    throw 'Chrome atau Edge tidak ditemukan.'
}

if (-not (Test-Path -LiteralPath $Html)) {
    throw "HTML tidak ditemukan: $Html"
}

$pdfPath = [System.IO.Path]::GetFullPath($Pdf)

if (Test-Path -LiteralPath $pdfPath) {
    Remove-Item -LiteralPath $pdfPath -Force
}

$profile = Join-Path $env:TEMP 'mom-client-package-profile'
$htmlPath = (Get-Item -LiteralPath $Html).FullName
$uri = ([System.Uri]$htmlPath).AbsoluteUri

& $browser `
    --headless=new `
    --disable-gpu `
    --no-sandbox `
    --no-first-run `
    --no-default-browser-check `
    --user-data-dir="$profile" `
    --no-pdf-header-footer `
    --virtual-time-budget=60000 `
    --print-to-pdf="$pdfPath" `
    $uri | Out-Null

if (-not (Test-Path -LiteralPath $pdfPath)) {
    throw 'PDF gagal dibuat.'
}

$python = Get-Command python -ErrorAction SilentlyContinue
if ($python) {
    & $python.Source (Join-Path $PSScriptRoot 'stamp-client-package-pdf.py') $pdfPath
} else {
    Write-Warning 'Python tidak ditemukan; nomor halaman tidak ditambahkan.'
}

Write-Output "PDF: $pdfPath"
