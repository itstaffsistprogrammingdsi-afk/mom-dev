# Status Implementasi vs Dokumen

Dokumen ini menjadi rujukan cepat untuk kondisi kode saat ini. PRD/Blueprint/ERD lama tetap disimpan sebagai dokumen historis; jika berbeda dengan halaman ini, kode dan migrasi adalah sumber kebenaran.

## Stack aktual

| Komponen | Aktual |
|---|---|
| Backend | Laravel 13, PHP 8.4 |
| Frontend | Inertia 3, Vue 3, TypeScript, Tailwind 3 |
| Editor | Tiptap 2.27 |
| Database | PostgreSQL 18.6 |
| Search | Generated `tsvector` + GIN + prefix query; trigram GIN untuk modul lain |
| Queue | Laravel database queue; worker terpisah `ai`, `notifications`, `default` |
| Cache | Database cache (dapat ditingkatkan ke Redis) |
| Kolaborasi | Hocuspocus/Yjs, token HMAC dengan TTL |
| 2FA | TOTP (`pragmarx/google2fa`) + recovery code |

## Peran

Kode memakai empat role: `admin`, `notulis`, `member`, `reader`.
“Kepala Divisi” bukan role terpisah; ditentukan oleh `divisions.head_id`.

## Aturan kerahasiaan yang berlaku

| Level | Akses |
|---|---|
| `open` | Semua user terautentikasi |
| `division` | Peserta/creator + user yang divisinya di-tag |
| `restricted` | Peserta/creator + user yang di-tag sebagai person |
| `secret` | Peserta/creator/admin; tag tidak membuka akses |

Notulen `secret` **tidak diproses AI** secara default (`AI_ALLOW_SECRET=false`).

## Fitur yang sudah ada di kode

- Decision CRUD + audit.
- Approver terpisah; self-approval ditolak; signature terkunci setelah meeting locked.
- Dashboard berbasis peran (draf, approval, tindak lanjut divisi).
- TOTP 2FA + recovery code.
- Search FTS dengan ranking.
- Health endpoint `/health`.
- Alert admin untuk AI/failed job.
- Retensi audit/notifikasi/versi via `mom:prune-data`.
- Integritas: unique peserta, CHECK constraint, jsonb notifications, `created_by` restrict.

## Selisih dokumen yang masih perlu diperbarui

- PRD masih menyebut Laravel 12, Inertia 2, Meilisearch + Redis.
- Blueprint menyebut Tiptap 3 dan `collab_documents`; kode memakai Tiptap 2 dan file storage Hocuspocus.
- ERD belum memuat `meeting_versions`, `meeting_signatures`, `quick_notes`, `quick_note_mentions`, `action_item_feedback`, `approver_id`, `search_vector`, dan kolom 2FA.
- Blueprint memiliki ID `F-50` ganda.
- README/PRD perlu diselaraskan untuk fitur Proposal/Laporan/Brosur, Quick Notes, signature, dan 2FA.
