<?php

$autoload = getenv('MOM_VENDOR_AUTOLOAD') ?: __DIR__.'/../vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "Autoload tidak ditemukan: {$autoload}\n");
    exit(1);
}

require $autoload;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

$docsDir = realpath(__DIR__.'/..');
$files = [
    '05-Blueprint-Sistem-Notulensi.md',
    '01-PRD-Sistem-Notulensi.md',
    '02-ERD-Sistem-Notulensi.md',
];

$mermaidBlocks = [];
$combined = "<!--COVER-->\n\n<!--TOCSTART-->\n\n[TOC]\n\n<!--TOCEND-->\n\n";

foreach ($files as $index => $file) {
    $path = $docsDir.'/'.$file;
    if (! is_file($path)) {
        fwrite(STDERR, "Dokumen tidak ditemukan: {$path}\n");
        exit(1);
    }

    $markdown = file_get_contents($path);

    $markdown = preg_replace_callback('/```mermaid\s*\n(.*?)```/s', function (array $matches) use (&$mermaidBlocks): string {
        $mermaidBlocks[] = trim($matches[1]);

        return "\n\n<!--MERMAID_".(count($mermaidBlocks) - 1)."-->\n\n";
    }, $markdown);

    $combined .= "<!--DOCSTART_{$index}-->\n\n".$markdown."\n\n<!--DOCEND_{$index}-->\n\n";
}

$environment = new Environment([
    'html_input' => 'allow',
    'allow_unsafe_links' => false,
    'max_nesting_level' => 30,
    'heading_permalink' => [
        'html_class' => 'heading-anchor',
        'id_prefix' => 'mom',
        'fragment_prefix' => 'mom',
        'symbol' => '#',
        'insert' => 'after',
        'apply_id_to_heading' => true,
        'aria_hidden' => true,
    ],
    'table_of_contents' => [
        'position' => 'placeholder',
        'placeholder' => '[TOC]',
        'min_heading_level' => 1,
        'max_heading_level' => 2,
        'normalize' => 'relative',
        'style' => 'bullet',
        'html_class' => 'table-of-contents',
    ],
    'slug_normalizer' => [
        'max_length' => 90,
    ],
]);

$environment->addExtension(new CommonMarkCoreExtension);
$environment->addExtension(new GithubFlavoredMarkdownExtension);
$environment->addExtension(new HeadingPermalinkExtension);
$environment->addExtension(new TableOfContentsExtension);

$converter = new MarkdownConverter($environment);
$html = (string) $converter->convert($combined);

$cover = <<<'HTML'
<section class="cover">
    <div class="cover-kicker">PAKET PRESENTASI KLIEN</div>
    <h1>Sistem Notulensi (MoM)<br>Terintegrasi AI</h1>
    <p class="cover-subtitle">Blueprint · Product Requirements · ERD dan Kamus Data</p>
    <div class="cover-rule"></div>
    <table class="cover-meta">
        <tr><td>Kode dokumen</td><td>DOC-PKG-MOM-001</td></tr>
        <tr><td>Versi</td><td>1.0</td></tr>
        <tr><td>Tanggal</td><td>9 November 2026</td></tr>
        <tr><td>Pemilik</td><td>Wafiq Lana Pradana</td></tr>
        <tr><td>Status</td><td>Draft untuk persetujuan klien</td></tr>
        <tr><td>Audiens</td><td>Manajemen, Product Owner, Kepala Divisi, IT, QA, Vendor</td></tr>
    </table>
    <p class="cover-note">Paket ini menggabungkan Blueprint (05), PRD (01), dan ERD (02). Bisnis Proses (03) dan Matriks Traceability (04) tersedia sebagai dokumen pendukung. Setiap bagian mempertahankan penomoran aslinya. Aspek komersial dan pembiayaan tidak dibahas dalam dokumen ini.</p>
</section>
HTML;

$html = str_replace('<!--COVER-->', $cover, $html);
$html = str_replace('<!--TOCSTART-->', '<section class="toc-page"><h1 class="toc-title">Daftar Isi</h1><p class="toc-note">Paket terdiri dari Blueprint, PRD, dan ERD. Klik judul untuk menuju bagian terkait.</p>', $html);
$html = str_replace('<!--TOCEND-->', '</section>', $html);

foreach ($files as $index => $file) {
    $html = str_replace("<!--DOCSTART_{$index}-->", '<section class="doc" id="doc-'.$index.'">', $html);
    $html = str_replace("<!--DOCEND_{$index}-->", '</section>', $html);
}

foreach ($mermaidBlocks as $index => $block) {
    $escaped = htmlspecialchars($block, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $html = str_replace(
        "<!--MERMAID_{$index}-->",
        '<div class="mermaid-wrap"><pre class="mermaid">'.$escaped.'</pre></div>',
        $html
    );
}

$css = <<<'CSS'
@page {
    size: A4;
    margin: 16mm 14mm 18mm;
}

* { box-sizing: border-box; }

html {
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

body {
    margin: 0;
    font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    font-size: 9.5pt;
    line-height: 1.55;
    color: #1f2937;
    background: #ffffff;
}

h1, h2, h3, h4 {
    color: #1F3864;
    line-height: 1.25;
}

h1 {
    font-size: 20pt;
    margin: 0 0 6mm;
    padding-bottom: 3mm;
    border-bottom: 3px solid #2F5597;
}

h2 {
    font-size: 13pt;
    margin: 8mm 0 3mm;
    padding-bottom: 1.5mm;
    border-bottom: 1px solid #dbe3f0;
    break-after: avoid;
}

h3 {
    font-size: 10.5pt;
    color: #2F5597;
    margin: 5mm 0 2mm;
    break-after: avoid;
}

p { margin: 0 0 2.8mm; }

ul, ol { margin: 0 0 3mm; padding-left: 5mm; }
li { margin-bottom: 1.2mm; }

strong { color: #111827; }

a { color: #1F3864; text-decoration: none; }

.heading-anchor { display: none; }

.cover {
    min-height: 250mm;
    padding: 26mm 18mm 18mm;
    background: #1F3864;
    color: #ffffff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    break-after: page;
}

.cover-kicker {
    font-size: 9pt;
    letter-spacing: 0.22em;
    color: #BFD0EA;
    margin-bottom: 7mm;
}

.cover h1 {
    font-size: 31pt;
    color: #ffffff;
    border: 0;
    margin: 0 0 5mm;
    padding: 0;
}

.cover-subtitle {
    font-size: 12pt;
    color: #D9E2F3;
    margin-bottom: 10mm;
}

.cover-rule {
    width: 45mm;
    height: 1.2mm;
    background: #8EAADB;
    margin-bottom: 12mm;
}

.cover-meta {
    width: 100%;
    max-width: 150mm;
    border-collapse: collapse;
    font-size: 9pt;
    margin: 0 0 10mm;
}

.cover-meta td {
    border: 0;
    border-bottom: 1px solid rgba(255,255,255,0.18);
    padding: 2.2mm 0;
    color: #E8EEF8;
    background: transparent !important;
}

.cover-meta td:first-child {
    width: 38mm;
    color: #BFD0EA;
}

.cover-note {
    font-size: 8.5pt;
    color: #BFD0EA;
    border-left: 2px solid #8EAADB;
    padding-left: 4mm;
    max-width: 140mm;
}

.toc-page {
    break-after: page;
}

.toc-title {
    font-size: 20pt;
    margin-top: 0;
}

.toc-note {
    color: #6b7280;
    font-size: 8.5pt;
    margin-bottom: 5mm;
}

.table-of-contents {
    font-size: 8.6pt;
}

.table-of-contents > ul {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

.table-of-contents > ul > li {
    margin-top: 3.2mm;
    font-weight: 600;
    color: #1F3864;
}

.table-of-contents ul ul {
    list-style: none;
    padding-left: 5mm;
    margin: 1mm 0 0;
    font-weight: 400;
    color: #374151;
}

.table-of-contents ul ul li {
    margin-bottom: 0.8mm;
}

.doc {
    break-before: page;
}

.doc:first-of-type {
    break-before: auto;
}

.doc h1 {
    margin-top: 0;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 3mm 0 5mm;
    font-size: 8.1pt;
    line-height: 1.4;
}

thead { display: table-header-group; }

th {
    background: #1F3864;
    color: #ffffff;
    text-align: left;
    padding: 2.2mm 2.4mm;
    border: 1px solid #1F3864;
    font-weight: 600;
}

td {
    padding: 1.8mm 2.4mm;
    border: 1px solid #d9e2f3;
    vertical-align: top;
}

tbody tr:nth-child(even) td {
    background: #F7F9FC;
}

tr { break-inside: avoid; }

blockquote {
    margin: 4mm 0;
    padding: 3mm 4mm;
    background: #EEF3FA;
    border-left: 3px solid #2F5597;
    color: #1F3864;
    break-inside: avoid;
}

blockquote p { margin: 0; }

pre:not(.mermaid) {
    background: #F6F8FA;
    border: 1px solid #e5e7eb;
    border-radius: 2mm;
    padding: 3mm;
    font-size: 8pt;
    white-space: pre-wrap;
    word-break: break-word;
    break-inside: avoid;
}

code {
    font-family: "Cascadia Mono", Consolas, monospace;
    font-size: 8.4pt;
    background: #F1F5F9;
    padding: 0.2mm 1mm;
    border-radius: 1mm;
}

pre code {
    background: transparent;
    padding: 0;
}

.mermaid-wrap {
    margin: 4mm 0 5mm;
    padding: 3mm;
    border: 1px solid #d9e2f3;
    border-radius: 2mm;
    background: #ffffff;
    text-align: center;
    break-inside: avoid;
}

.mermaid {
    margin: 0;
    background: transparent;
    border: 0;
    padding: 0;
    font-size: 0;
}

.mermaid svg {
    max-width: 100%;
    max-height: 215mm;
    height: auto;
    width: auto;
}

CSS;

$document = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Blueprint, PRD, dan ERD - Sistem Notulensi (MoM) Terintegrasi AI</title>
    <style>{$css}</style>
</head>
<body>
{$html}
<script src="../mom/node_modules/mermaid/dist/mermaid.min.js"></script>
<script>
    mermaid.initialize({
        startOnLoad: true,
        securityLevel: 'loose',
        theme: 'base',
        fontFamily: 'Segoe UI, Arial, sans-serif',
        themeVariables: {
            primaryColor: '#EEF3FA',
            primaryTextColor: '#1F3864',
            primaryBorderColor: '#2F5597',
            lineColor: '#2F5597',
            secondaryColor: '#D9E2F3',
            secondaryTextColor: '#1F3864',
            tertiaryColor: '#ffffff',
            tertiaryTextColor: '#1F3864',
            textColor: '#1F3864',
            fontSize: '13px'
        },
        flowchart: { htmlLabels: true, curve: 'basis', useMaxWidth: true },
        er: { useMaxWidth: true, fontSize: 11 },
        timeline: { useMaxWidth: true }
    });
</script>
</body>
</html>
HTML;

$output = $docsDir.'/Blueprint-PRD-ERD-Sistem-Notulensi.html';
file_put_contents($output, $document);

echo "HTML: {$output}\n";
echo 'Dokumen: '.count($files)."\n";
echo 'Diagram Mermaid: '.count($mermaidBlocks)."\n";
echo 'Ukuran: '.number_format(strlen($document) / 1024, 1)." KB\n";
