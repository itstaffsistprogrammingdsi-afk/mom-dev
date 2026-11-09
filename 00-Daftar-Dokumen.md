# Dokumentasi Sistem Notulensi (MoM)

**Paket dokumen:** DOC-MOM-2026-01  
**Versi:** 1.0  
**Tanggal:** 26 Oktober 2026  
**Status:** Final draft  
**Pemilik:** Wafiq Lana Pradana  
**Audiens:** Manajemen, Product Owner, tim developer, QA, admin sistem, notulis champion

## Tujuan Paket Dokumen

Paket ini mendokumentasikan Sistem Notulensi (Minutes of Meeting/MoM) Terintegrasi AI dari sisi produk, data, dan proses bisnis. Dokumen disusun agar dapat digunakan sebagai acuan yang sama oleh manajemen, tim teknis, dan pengguna operasional.

Aspek komersial dan pembiayaan tidak dibahas dalam paket ini.

## Daftar Dokumen

| No | Dokumen | Kode | Isi |
| --- | --- | --- | --- |
| 00 | Daftar Dokumen | DOC-MOM-2026-01 | Navigasi, konvensi, dan kontrol dokumen |
| 01 | PRD Sistem Notulensi | DOC-PRD-MOM-001 | Masalah, tujuan, persona, kebutuhan, aturan, arsitektur, rilis, risiko |
| 02 | ERD & Kamus Data | DOC-ERD-MOM-001 | Entitas, relasi, kamus data, enum, indeks, siklus hidup data |
| 03 | Bisnis Proses | DOC-BPM-MOM-001 | Alur end-to-end, swimlane, state machine, notifikasi, RACI, SLA |
| 04 | Matriks Traceability | DOC-TRC-MOM-001 | Keterkaitan kebutuhan, proses, data, dan status implementasi |
| 05 | Blueprint Sistem Notulensi | DOC-BLP-MOM-001 | Ringkasan presentasi klien: modul, fitur, peran, proses, data, arsitektur, dan rilis |
| 06 | Paket Blueprint, PRD, dan ERD | DOC-PKG-MOM-001 | Paket presentasi klien gabungan untuk audiens teknis |

**Dokumen konsolidasi:** `Dokumentasi-Sistem-Notulensi.pdf`  
**Versi Word:** `Dokumentasi-Sistem-Notulensi.docx` (disarankan) dan `Dokumentasi-Sistem-Notulensi.doc` (format lama)  
**Sumber HTML:** `Dokumentasi-Sistem-Notulensi.html`

## Dokumen Presentasi Klien

### Paket lengkap untuk klien teknis

Paket ini menggabungkan Blueprint, PRD, dan ERD dalam satu dokumen dengan cover, daftar isi, dan diagram:

- Markdown: `06-Blueprint-PRD-ERD-Sistem-Notulensi.md`
- PDF: `Blueprint-PRD-ERD-Sistem-Notulensi.pdf`
- Word: `Blueprint-PRD-ERD-Sistem-Notulensi.docx`
- HTML sumber PDF: `Blueprint-PRD-ERD-Sistem-Notulensi.html`

### Blueprint standalone

- Markdown: `05-Blueprint-Sistem-Notulensi.md`
- PDF: `Blueprint-Sistem-Notulensi.pdf`
- Word: `Blueprint-Sistem-Notulensi.docx`
- HTML sumber PDF: `Blueprint-Sistem-Notulensi.html`

Dokumen 05 adalah ringkasan presentasi; dokumen 06 menggabungkan 05, PRD (01), dan ERD (02) untuk audiens teknis. Sumber teknis lengkap tetap dokumen 01-04.

## Urutan Membaca

1. **Manajemen:** 01 PRD bagian 1–4, 12, 15, dan 16; 03 bagian 3–5 dan 15.
2. **Product Owner/Notulis:** 01 PRD lengkap; 03 bagian 4–12.
3. **Developer/QA:** 01 bagian 7–14; 02 lengkap; 03 bagian 13–17; 04 lengkap.
4. **Admin sistem:** 01 bagian 12; 02 bagian 9–11; 03 bagian 12 dan 16.

## Konvensi

- **P0/P1/P2:** prioritas rilis.
- **BR-xx:** aturan bisnis.
- **F-xx:** kebutuhan fungsional.
- **US-xx:** cerita pengguna.
- **EX-xx:** pengecualian proses.
- **P1–P8:** kode proses bisnis.
- Status implementasi: **Sudah**, **Sebagian**, **Kerangka**, **Belum**.
- Istilah asing yang lazim dipertahankan (action item, mention, tag, queue) dengan penjelasan di glosarium PRD.

## Kontrol Dokumen

| Aspek | Ketentuan |
| --- | --- |
| Pemilik dokumen | Product Owner |
| Review teknis | Tech Lead |
| Review proses | Kepala Divisi Pilot |
| Persetujuan | Manajemen |
| Siklus review | Setiap akhir fase atau bila ada perubahan scope |
| Sumber kebenaran data | Migrasi dan model pada repositori |
| Sumber kebenaran proses | Dokumen 03 dan kesepakatan divisi pilot |

## Perubahan Dokumen

Setiap perubahan requirement wajib memperbarui:

1. `01-PRD-Sistem-Notulensi.md` (kebutuhan/aturan).
2. `02-ERD-Sistem-Notulensi.md` (bila menyentuh data).
3. `03-Bisnis-Proses-Sistem-Notulensi.md` (bila menyentuh alur).
4. `04-Matriks-Traceability.md` (status dan keterkaitan).
5. PDF konsolidasi.

## Catatan Implementasi

Dokumen disusun berdasarkan scaffold aplikasi Laravel 12 + Inertia + Vue 3 + TypeScript + PostgreSQL. Status implementasi pada dokumen 04 mencerminkan kondisi scaffold, bukan target akhir. Beberapa fitur sudah memiliki skema dan antarmuka tetapi belum memiliki integrasi penuh (AI, notifikasi otomatis, kolaborasi persisten).

## Regenerasi PDF

```bash
python scripts/render-diagrams.py
python scripts/split-diagrams.py
php scripts/build-docs.php
powershell -ExecutionPolicy Bypass -File scripts/export-docs-pdf.ps1
php scripts/build-docx.php
```

PDF dan Word dihasilkan dari dokumen Markdown dan diagram Mermaid, sehingga perubahan dokumen cukup dilakukan pada sumber Markdown. Gambar diagram disimpan di `docs/doc-images/`.
