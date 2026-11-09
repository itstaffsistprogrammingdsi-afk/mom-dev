<?php

$root = realpath(__DIR__.'/..');

$sources = [
    '05-Blueprint-Sistem-Notulensi.md',
    '01-PRD-Sistem-Notulensi.md',
    '02-ERD-Sistem-Notulensi.md',
];

$frontMatter = <<<'MD'
# Paket Presentasi Klien - Sistem Notulensi (MoM) Terintegrasi AI

**Kode dokumen:** DOC-PKG-MOM-001  
**Versi:** 1.0  
**Tanggal:** 9 November 2026  
**Status:** Draft untuk persetujuan klien  
**Pemilik:** Wafiq Lana Pradana  
**Audiens:** Manajemen, Product Owner, Kepala Divisi, IT, QA, Vendor

> Paket ini menggabungkan Blueprint Sistem (05), Product Requirements Document (01), dan ERD & Kamus Data (02). Bisnis Proses (03) dan Matriks Traceability (04) tersedia sebagai dokumen pendukung terpisah. Setiap bagian mempertahankan penomoran aslinya. Aspek komersial dan pembiayaan tidak dibahas dalam dokumen ini.

## Isi Paket

| Bagian | Dokumen | Fokus |
|---|---|---|
| I | Blueprint Sistem Notulensi | Modul, fitur, peran, proses, arsitektur, dan rilis |
| II | PRD Sistem Notulensi | Masalah, tujuan, kebutuhan fungsional, aturan bisnis, akses, dan NFR |
| III | ERD dan Kamus Data | Entitas, relasi, kamus data, indeks, dan siklus hidup |

---

MD;

$combined = $frontMatter;

foreach ($sources as $index => $source) {
    $path = $root.'/'.$source;

    if (! is_file($path)) {
        fwrite(STDERR, "Dokumen tidak ditemukan: {$path}\n");
        exit(1);
    }

    if ($index > 0) {
        $combined .= "\n\n---\n\n";
    }

    $combined .= rtrim((string) file_get_contents($path))."\n";
}

$output = $root.'/06-Blueprint-PRD-ERD-Sistem-Notulensi.md';
file_put_contents($output, $combined);

echo "Markdown: {$output}\n";
echo 'Sumber: '.count($sources)." dokumen\n";
echo 'Ukuran: '.number_format(strlen($combined) / 1024, 1)." KB\n";
