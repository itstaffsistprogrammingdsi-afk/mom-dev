# Sistem Notulensi (MoM) Terintegrasi AI

Aplikasi internal untuk mencatat, merangkum, mendistribusikan, dan menindaklanjuti hasil rapat.

## Stack

- Laravel 13 + PHP 8.4
- Inertia 3 + Vue 3 + TypeScript
- Tailwind CSS + shadcn-vue
- Tiptap + Yjs/Hocuspocus
- PostgreSQL 18
- Laravel Queue + Scheduler
- AI provider-agnostik (OpenAI-compatible) dengan mode mock

## Kebutuhan Lokal

- PHP 8.4 dengan ekstensi `pdo_pgsql`, `mbstring`, `intl`, `gd`, `zip`
- Composer 2
- Node.js 20+ dan npm
- PostgreSQL 16+
- Chrome/Edge (untuk ekspor PDF dokumentasi)

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Buat database PostgreSQL:

```sql
CREATE DATABASE mom_dev;
```

Sesuaikan koneksi database di `.env`, lalu:

```bash
php artisan migrate --seed
npm install
npm run build
```

Jalankan aplikasi:

```bash
php artisan serve --port=8100
```

Port 8100 dipakai agar tidak bertabrakan dengan aplikasi lain yang mungkin berjalan di port 8000.

Untuk pengembangan frontend:

```bash
npm run dev
```

## Akun Demo

| Peran | Email | Password |
|---|---|---|
| Admin | admin@mom.test | password |
| Notulis | notulis@mom.test | password |
| Kepala Divisi IT | manager@mom.test | password |
| Keuangan | keuangan@mom.test | password |
| SDM | hr@mom.test | password |
| Staf IT | staff@mom.test | password |
| Direksi | direksi@mom.test | password |

Registrasi publik aktif; pengguna baru otomatis mendapat peran Anggota. Admin dapat mengubah peran dan menonaktifkan akun dari menu Admin > Pengguna.

## AI

Secara default aplikasi memakai mode `mock` agar demo berjalan tanpa API key.

Untuk memakai provider OpenAI-compatible, ubah `.env`:

```env
AI_DRIVER=openai
AI_BASE_URL=https://api.openai.com/v1
AI_API_KEY=sk-...
AI_MODEL=gpt-4o-mini
```

Provider yang kompatibel dengan Chat Completions (OpenAI, OpenRouter, DeepSeek, Ollama, dan lainnya) dapat digunakan dengan mengubah `AI_BASE_URL` dan `AI_MODEL`.

## Kolaborasi Real-time

Jalankan service Hocuspocus:

```bash
npm --prefix collab install
npm --prefix collab start
```

Service kolaborasi membaca `.env` dari root proyek secara otomatis. Konfigurasi default: `ws://127.0.0.1:1234`.

```env
COLLAB_SECRET=change-me-in-production
COLLAB_PORT=1234
COLLAB_URL=ws://localhost:1234
COLLAB_TOKEN_TTL=3600
```

Jika service kolaborasi tidak tersedia, editor tetap dapat dipakai dalam mode tunggal.

## Queue dan Scheduler

Lokal default memakai `QUEUE_CONNECTION=sync`. Untuk produksi, gunakan `database` atau Redis dan jalankan worker:

```bash
php artisan queue:work
```

Pengingat action item dijadwalkan setiap pukul 09.00:

```bash
php artisan mom:remind-action-items
```

Jalankan scheduler produksi:

```bash
php artisan schedule:work
```

## Template Dokumen

Admin dapat mengelola template di `/admin/template`. Template mendukung beberapa jenis dokumen:

- Notulen Rapat
- Proposal Proyek
- Laporan
- Brosur
- Dokumen lain

Setiap template memiliki struktur Tiptap sendiri. Saat pengguna memilih template di halaman buat dokumen, isi editor otomatis mengikuti struktur template tersebut.

## Peran dan Hak Akses

Penjelasan peran dan matriks hak akses tersedia di `/admin/peran`.

| Peran | Ringkasan |
|---|---|
| Admin | Akses penuh ke dokumen, pengguna, divisi, topik, template, dan audit. |
| Notulis | Membuat/menyunting draf, memfinalkan, mengelola action item, menjalankan AI. |
| Anggota | Membaca sesuai akses, memperbarui status tugas sebagai PIC. |
| Pembaca | Membaca sesuai akses tanpa menyunting. |

Kepala divisi tidak memakai peran terpisah; ia memakai peran Notulis atau Anggota dan ditetapkan sebagai kepala divisi pada data divisi.

Dikte suara memerlukan Chrome/Edge, izin mikrofon, dan HTTPS (atau `localhost`). Audio tidak disimpan oleh aplikasi.

## Ekspor dan Bagikan

- Dari halaman dokumen, gunakan **Ekspor > PDF** atau **Ekspor > Word (.docx)**.
- Tombol **Bagikan** mengirim email ke maksimal 10 penerima, dengan opsi melampirkan salinan PDF.
- Untuk dokumen berlabel terbatas/rahasia, pengirim wajib mengonfirmasi bahwa penerima berhak.
- Lokal default memakai `MAIL_MAILER=log`; atur SMTP di `.env` untuk pengiriman nyata.

## Halaman Login

Halaman login/register memakai view referensi dari `resources/notula-login-source` yang diadaptasi ke brand MoM. Aset visual hasil rebranding disimpan di `public/images/mom-auth-visual.webp`, dengan style terisolasi di `resources/css/auth.css`.

## Google Login (opsional)

Login Google memakai OAuth 2.0 langsung tanpa dependency tambahan. Isi `.env`:

```env
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost:8100/auth/google/callback
```

Daftarkan redirect URI yang sama di Google Cloud Console. Jika kredensial kosong, tombol Google tampil nonaktif dengan label “belum aktif”.

## Tema

Pilih **Terang**, **Gelap**, atau **Ikuti sistem** dari menu tema di header, halaman Pengaturan > Tampilan, atau halaman login. Pilihan disimpan di browser dan mode sistem mengikuti perubahan tema perangkat.

## Tagging dan Dikte Suara

- Ketik `@` di editor untuk memilih orang atau divisi.
- Ketik `#` untuk memilih topik.
- Tombol mikrofon di toolbar editor menjalankan dikte suara (Chrome/Edge, bahasa Indonesia). Audio tidak disimpan oleh aplikasi.

## Perintah Verifikasi

```bash
php artisan test
npm run typecheck
npm run build
```

Opsional smoke test UI (membutuhkan Chrome dan server berjalan di port 8100):

```bash
node scripts/smoke-ui.mjs
node scripts/smoke-collab.mjs
```

## Struktur Fitur Utama

- `/dashboard` ringkasan aktivitas dan tugas
- `/notulen` daftar, pencarian, dan filter notulen
- `/notulen/{id}` detail, resume AI, action item, lampiran, riwayat versi
- `/notulen/{id}/sunting` editor kolaboratif dengan autosave
- `/terkait-saya` notulen dan tugas yang menyebut pengguna/divisinya
- `/topik`, `/divisi/{slug}`, `/orang/{id}` halaman pengetahuan
- `/tugas` daftar/board action item
- `/notifikasi` notifikasi in-app
- `/admin/*` pengguna, divisi, topik, template, audit log

## Dokumentasi

- `00-Daftar-Dokumen.md`
- `01-PRD-Sistem-Notulensi.md`
- `02-ERD-Sistem-Notulensi.md`
- `03-Bisnis-Proses-Sistem-Notulensi.md`
- `04-Matriks-Traceability.md`
- `05-Blueprint-Sistem-Notulensi.md`
- `06-Blueprint-PRD-ERD-Sistem-Notulensi.md`
- `Blueprint-PRD-ERD-Sistem-Notulensi.pdf` dan `.docx`

Regenerate paket dokumentasi:

```bash
powershell -NoProfile -ExecutionPolicy Bypass -File scripts/build-client-package.ps1
```
"# mom-dev" 
