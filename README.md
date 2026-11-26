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

## Akun Demo (khusus lokal/testing)

> **Jangan jalankan `php artisan migrate --seed` di production.** Akun demo memakai password default dan hanya untuk pengembangan. Untuk production, buat admin melalui prosedur aman dan gunakan password acak/forced reset.

| Peran | Email | Password (lokal) |
|---|---|---|
| Admin | admin@mom.test | password |
| Notulis | notulis@mom.test | password |
| Kepala Divisi IT | manager@mom.test | password |
| Keuangan | keuangan@mom.test | password |
| SDM | hr@mom.test | password |
| Staf IT | staff@mom.test | password |
| Direksi | direksi@mom.test | password |

Registrasi publik aktif secara default hanya saat `APP_ENV=local`; pengguna baru otomatis mendapat peran Anggota. Untuk production, set `REGISTRATION_ENABLED=false` atau batasi ke domain/invite, lalu gunakan admin untuk mengelola akun.

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

Batas dan efisiensi:

- `AI_DAILY_GENERATIONS=20` — kuota permintaan resume AI per pengguna per hari. Set `0` untuk tanpa batas.
- `AI_MAX_INPUT_CHARS=12000` — isi notulen dipotong sebelum dikirim ke provider agar prompt tidak membengkak.
- Respons dengan isi notulen identik dan tier/model sama memakai cache 6 jam, sehingga permintaan ulang tidak menagih provider lagi.
- Setiap resume menyimpan `sources` (judul + URL notulen) dan `usage` (hash isi, jumlah karakter, estimasi token) untuk audit dan sitasi.
- Versi resume unik per `(meeting_id, scope, version)` di database; permintaan bersamaan diserialisasi dengan row lock.

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

## Retensi Data

Retensi default: audit log 365 hari, notifikasi terbaca 90 hari, versi meeting lama 90 hari (versi terbaru selalu disimpan).

```bash
# dry-run
php artisan mom:prune-data

# eksekusi
php artisan mom:prune-data --force
```

Scheduler menjalankan `mom:prune-data --force` setiap pukul 03.00 Asia/Jakarta.

## Autentikasi Dua Faktor

Buka **Pengaturan > Autentikasi Dua Faktor** untuk mengaktifkan TOTP (Google Authenticator/Microsoft Authenticator). Setelah aktif, login memerlukan kode 6 digit; recovery code dapat dipakai satu kali.

## Proyeksi Akses Meeting

Tabel `meeting_access` menyimpan grant hasil tag divisi/orang untuk notulen `division` dan `restricted`. Grant diperbarui otomatis oleh observer saat tag, divisi pengguna, status aktif, atau tingkat kerahasiaan berubah. Notulen `open` memakai aturan langsung, `secret` hanya peserta/pembuat/admin yang ditunjuk, dan akses langsung lewat peserta/admin tetap dicek tanpa proyeksi.

Setelah deploy atau jika proyeksi dicurigai tidak sinkron:

```bash
php artisan mom:rebuild-meeting-access
```

Perintah ini membangun ulang seluruh proyeksi dan aman dijalankan berulang. Cache statistik dashboard disimpan 30 detik per pengguna; cache jumlah notifikasi belum dibaca disimpan 15 detik dan dibersihkan saat pengguna menandai notifikasi terbaca.

## Pencarian Notulen

Pencarian daftar notulen memakai generated `tsvector` PostgreSQL (`title` A, `agenda` B, `content_text` C), GIN index, prefix query, dan ranking `ts_rank`. Trigram GIN tetap tersedia untuk fallback pencarian substring di modul lain.

Benchmark lokal 100.000 notulen (`scripts/benchmark-search.php 100000`, hasil di `docs/benchmarks/search-100000.json`):

| Jalur | Median | Catatan |
|---|---|---|
| FTS + ranking | 19,7 ms | `Bitmap Index Scan on meetings_search_vector_gin`, 10.001 hasil |
| LIKE substring | 3,7 ms | Memakai trigram GIN, tanpa ranking |

FTS lebih mahal karena menghitung `ts_rank` untuk seluruh hasil, tetapi tetap jauh di bawah target 3 detik dan skalanya lebih stabil untuk kata kunci jarang. LIKE tetap dipertahankan untuk pencarian substring cepat pada modul lain.

## Pencarian Global dan Notifikasi

- Tekan `Ctrl+K` (atau `Cmd+K`) untuk membuka command palette: cari notulen, topik, orang, dan navigasi dari satu tempat.
- Preferensi notifikasi ada di **Pengaturan > Notifikasi**: aktif/nonaktifkan kanal in-app, salinan email, serta kategori notulen, action item, dan Quick Note. Peringatan sistem untuk admin tetap tampil di aplikasi.
- `MeetingEditor` dimuat secara async di halaman buat/sunting dan mode dokumen; mode sheet tidak memuat editor Tiptap.

## Health Check & Runbook Singkat

Endpoint publik `GET /health` memeriksa database, cache, konfigurasi queue, konfigurasi AI, dan status Hocuspocus. Status `503` diberikan jika komponen inti gagal; status Hocuspocus dilaporkan terpisah sebagai `collab`.

- **Queue macet / email tidak terkirim:** cek `php artisan queue:work --queue=ai,notifications,default`, lalu `failed_jobs`; restart worker setelah deploy.
- **AI down:** cek `AI_BASE_URL`, `AI_API_KEY`, dan `failed_jobs`; job AI akan retry dengan backoff, lalu masuk `failed_jobs` setelah habis percobaan.
- **Hocuspocus down:** editor tetap dapat dipakai mode tunggal; cek `COLLAB_URL` dan log `collab/server.js`.
- **Rollback:** revert commit aplikasi, lalu jalankan `php artisan migrate:rollback --step=1` hanya untuk migrasi index baru.

## Pemeriksaan Integritas

Jalankan dry-run sebelum migrasi integritas:

```bash
php artisan mom:check-integrity
```

Untuk membersihkan duplikat peserta setelah laporan ditinjau:

```bash
php artisan mom:check-integrity --fix-participants
```

Di production, jalankan dry-run ini **sebelum** migrasi integritas (`2026_11_26_000006` sampai `000008`). Migrasi unique peserta dan konversi `notifications.data` ke `jsonb` akan gagal bila masih ada duplikat/JSON tidak valid; perbaiki datanya lebih dulu. Pemeriksaan yang sama melaporkan duplikat resume AI (`meeting_id` + `version`) sebelum migrasi index unik `000012`. Setelah migrasi `000013`, jalankan `php artisan mom:rebuild-meeting-access` sekali untuk mengisi proyeksi akses.

## Queue dan Scheduler

Default `.env.example` memakai `QUEUE_CONNECTION=database`, sehingga worker wajib berjalan agar job (termasuk resume AI) diproses. Untuk produksi gunakan `database` atau Redis lalu jalankan worker:

```bash
php artisan queue:work --queue=ai,notifications,default
```

Untuk produksi, jalankan worker terpisah untuk `ai` dan `notifications` agar job AI panjang tidak memblokir email/notifikasi.

Pengingat action item dijadwalkan setiap pukul 09.00:

```bash
php artisan mom:remind-action-items
```

Perintah ini memakai catch-up window: pengingat H-3 dikirim selama tenggat masih 2–3 hari lagi, H-1 dikirim saat tenggat tinggal 0–1 hari, dan keterlambatan dicek ulang setiap hari. Jika scheduler sempat mati, pengingat yang terlewat tetap dikirim saat scheduler hidup lagi, tanpa duplikasi.

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

Audit log mencatat login/logout, pembukaan Quick Note, perubahan divisi/topik, perubahan pengguna/template, permintaan AI, dan aksi dokumen penting.

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
GOOGLE_ALLOWED_DOMAINS=perusahaan.com
```

Daftarkan redirect URI yang sama di Google Cloud Console. Jika kredensial kosong, tombol Google tampil nonaktif dengan label “belum aktif”. Auto-provision akun Google baru **mati secara default**; hanya user yang sudah terdaftar yang dapat masuk. Set `GOOGLE_AUTO_PROVISION=true` hanya jika kebijakan perusahaan mengizinkan.

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
