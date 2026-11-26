# Status Implementasi vs Dokumen

Dokumen ini menjadi rujukan cepat untuk kondisi kode saat ini. PRD/Blueprint/ERD lama tetap disimpan sebagai dokumen historis; jika berbeda dengan halaman ini, kode dan migrasi adalah sumber kebenaran. PRD, ERD, dan Blueprint sudah diberi addendum tertanggal 26 November 2026.

## Stack aktual

| Komponen | Aktual |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia 3, Vue 3, TypeScript, Tailwind 3 |
| Editor | Tiptap 2.27, dimuat async di halaman yang membutuhkan |
| Database | PostgreSQL 18.6 |
| Search | Generated `tsvector` + GIN + prefix query; trigram GIN untuk modul lain |
| Queue | Laravel database queue; worker terpisah `ai`, `notifications`, `default` |
| Cache | Database cache (dapat ditingkatkan ke Redis) |
| Kolaborasi | Hocuspocus/Yjs, token HMAC dengan TTL |
| 2FA | TOTP (`pragmarx/google2fa`) + recovery code |
| Akses notulen | `meeting_access` projection + Aturan langsung untuk peserta/admin |

## Peran

Kode memakai empat role: `admin`, `notulis`, `member`, `reader`.
“Kepala Divisi” bukan role terpisah; ditentukan oleh `divisions.head_id`.

## Aturan kerahasiaan yang berlaku

| Level | Akses |
|---|---|
| `open` | Semua user terautentikasi |
| `division` | Peserta/creator + user yang divisinya di-tag |
| `restricted` | Peserta/creator + user yang di-tag sebagai person |
| `secret` | Peserta/creator + admin dokumen yang ditunjuk; tag tidak membuka akses |

Notulen `secret` **tidak diproses AI** secara default (`AI_ALLOW_SECRET=false`).

## Fitur yang sudah ada di kode

- Decision CRUD + audit.
- Approver terpisah; self-approval ditolak; signature terkunci setelah meeting locked.
- Dashboard berbasis peran (draf, approval, tindak lanjut divisi) dengan cache statistik 30 detik.
- TOTP 2FA + recovery code.
- Search FTS dengan ranking; benchmark 100k = 19,7 ms median.
- Command palette `Ctrl+K` untuk notulen, topik, orang, dan navigasi.
- Preferensi notifikasi per kanal dan kategori.
- Health endpoint `/health`.
- Alert admin untuk AI/failed job.
- Retensi audit/notifikasi/versi via `mom:prune-data`.
- Integritas: unique peserta, CHECK constraint, jsonb notifications, `created_by` restrict.
- AI: kuota harian, cache 6 jam, sitasi sumber, usage hash, unique version, row lock.
- Akses: proyeksi `meeting_access`, rebuild via `mom:rebuild-meeting-access`.
- Reminder H-3/H-1/overdue dengan catch-up.
- Audit login/logout, Quick Note, divisi, topik, pengguna, template.

## Migrasi baru (26 November 2026)

| Migrasi | Isi |
|---|---|
| `000006`–`000008` | Unique peserta, CHECK enum, jsonb notifications, `created_by` restrict |
| `000009` | Generated `search_vector` + GIN |
| `000010` | Kolom 2FA pengguna |
| `000011` | Tabel `meeting_admins` |
| `000012` | Unique resume AI + indeks kuota |
| `000013` | Tabel `meeting_access` |

## Selisih dokumen yang masih perlu diperbarui

- Diagram ERD utama belum menggambar tabel Quick Note, signature, version, admin dokumen, dan `meeting_access`; addendum sudah mencantumkannya.
- PRD masih memuat sebagian narasi roadmap SSO/pgvector/Redis; addendum menyatakan itu roadmap, bukan fitur aktif.
- Blueprint masih menyebut Hocuspocus 4; kode memakai Hocuspocus/Yjs tanpa tabel `collab_documents`.
- Matriks traceability belum memetakan fitur baru (decision, Quick Note, 2FA, command palette, preferensi notifikasi).
