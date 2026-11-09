# Matriks Traceability — Sistem Notulensi (MoM)

**Kode dokumen:** DOC-TRC-MOM-001  
**Versi:** 1.0  
**Tanggal:** 26 Oktober 2026  
**Status:** Final draft

## 1. Tujuan

Dokumen ini menghubungkan kebutuhan produk (PRD), proses bisnis, entitas data (ERD), dan status implementasi. Matriks ini digunakan untuk memastikan tidak ada kebutuhan yang hilang, memahami dampak perubahan, dan merencanakan pekerjaan berikutnya.

## 2. Legenda Status

| Status | Arti |
| --- | --- |
| **Sudah** | Berfungsi di scaffold dan teruji |
| **Sebagian** | Sebagian alur berjalan; beberapa kemampuan belum |
| **Kerangka** | Skema/UI tersedia, integrasi utama belum |
| **Belum** | Belum diimplementasikan |

## 3. Tujuan → Metrik → Proses

| Tujuan | Metrik | Proses terkait | Status pengukuran |
| --- | --- | --- | --- |
| Notulen tersentralisasi | ≥ 90% rapat tercatat | P1, P2, P4 | Belum ada baseline |
| Distribusi cepat | ≤ 24 jam | P5 | Belum ada log kirim |
| Tindak lanjut jelas | 100% PIC & tenggat | P2, P4 | Validasi finalisasi tersedia |
| Penyelesaian tepat waktu | ≥ 80% | P6 | Sebagian (status tugas) |
| Efisiensi membaca | ≥ 70% resume dipakai | P3 | Kerangka |
| Adopsi | Semua divisi | P1–P8 | Belum diukur |
| Kualitas AI | ≥ 80% tanpa koreksi besar | P3 | Kerangka |
| Kinerja | ≤ 2 dtk / ≤ 3 dtk | P2, P5 | Belum diukur |

## 4. Traceability Kebutuhan Fungsional

| ID | Kebutuhan | Proses | Entitas utama | Status | Catatan |
| --- | --- | --- | --- | --- | --- |
| F-01 | Editor dokumen kaya | P2 | `meetings.content` | Sudah | Tiptap, tabel, daftar, tautan, autosave; gambar melalui lampiran |
| F-02 | Template standar | P1 | `meeting_templates`, `meetings` | Sudah | Template dasar dan struktur default |
| F-03 | Template per jenis rapat | P1 | `meeting_templates.meeting_type` | Sudah | Admin dapat membuat, menyunting, dan menonaktifkan template |
| F-04 | Kolaborasi real-time & versi | P2 | `meetings`, Hocuspocus | Sudah | Token HMAC, presence, snapshot versi, dan restore |
| F-05 | Tabel action item | P2, P4 | `action_items` | Sudah | CRUD, update status, dan board |
| F-06 | Draf, final, kunci | P4 | `meetings.status` | Sudah | Final, lock/unlock admin, dan validasi finalisasi |
| F-07 | Lampiran file & tautan | P2 | `attachments` | Sudah | Upload file/link, unduh dengan policy, dan hapus |
| F-08 | Ekspor PDF/Word | P5 | `meetings`, `ai_summaries` | Belum | Rencana fase lanjutan |
| F-10 | Tag orang | P2 | `tags` | Sudah | Mention + sinkronisasi |
| F-11 | Tag divisi | P2 | `tags` | Sudah | Mention + sinkronisasi |
| F-12 | Tag topik | P2 | `tags`, `topics` | Sudah | Mention + halaman topik |
| F-13 | Tag per bagian | P2 | `tags.position` | Sebagian | Kolom tersedia; UI posisi belum |
| F-14 | Notifikasi & Terkait Saya | P5 | `notifications`, `tags` | Sudah | Halaman Terkait Saya dan notifikasi otomatis |
| F-15 | Banyak tag sekaligus | P2 | `tags` | Sudah | Sinkronisasi per tipe/target |
| F-16 | Tamu eksternal | P1 | `meeting_participants` | Sebagian | Kolom tersedia; UI tamu belum |
| F-20 | Halaman Terkait Saya | P5 | `meetings`, `action_items`, `tags` | Sudah | Query akses dan tampilan |
| F-21 | Halaman topik | P5 | `topics`, `tags`, `decisions` | Sudah | Daftar dan detail topik |
| F-22 | Halaman divisi/orang | P5 | `divisions`, `users`, `tags` | Sudah | Halaman divisi dan profil orang dengan filter akses |
| F-23 | Pencarian + filter | P5 | `meetings`, Scout | Sebagian | Scout driver database; Meilisearch produksi belum |
| F-24 | Board action item | P6 | `action_items` | Sudah | Tampilan daftar dan kanban dengan filter |
| F-25 | Dashboard ringkasan | P7 | `meetings`, `action_items` | Sebagian | Statistik dasar tersedia |
| F-30 | Notifikasi email & in-app | P5 | `notifications` | Sudah | Database + mail notification, resolver penerima, dan halaman notifikasi |
| F-31 | Pengingat H-3/H-1 | P6 | `action_items`, scheduler | Sudah | Command scheduler H-3, H-1, dan overdue |
| F-32 | Integrasi chat | P5 | `notifications` | Belum | Fase 3 |
| F-33 | Integrasi kalender | P1 | `meetings`, `meeting_participants` | Belum | Fase 2 |
| F-34 | Rekap mingguan | P7 | `ai_summaries`, `notifications` | Belum | Fase 2 |
| F-40 | Resume per notulen | P3 | `ai_summaries` | Sudah | Adapter OpenAI-compatible + mock, job, UI verifikasi |
| F-41 | Ekstraksi action item | P3 | `ai_summaries`, `action_items` | Sudah | Usulan action item dapat diterapkan setelah verifikasi |
| F-42 | Saran tag | P3 | `ai_summaries`, `tags` | Sebagian | AI mengembalikan saran tag; UI penerapan tag belum |
| F-43 | Resume lintas notulen | P7 | `ai_summaries` | Belum | Fase 2 |
| F-44 | Tanya-jawab AI | P7 | `ai_summaries`, pgvector | Belum | Fase 2 |
| F-45 | Resume per peran | P7 | `ai_summaries.audience` | Belum | Fase 3 |
| F-46 | Transkripsi rekaman | P2 | `attachments`, `meetings` | Belum | Fase 3 |
| F-47 | Penerjemahan resume | P3 | `ai_summaries.language` | Belum | Fase 3 |
| F-50 | Manajemen pengguna/divisi/SSO | P8 | `users`, `divisions` | Sebagian | CRUD admin; SSO belum |
| F-51 | Manajemen topik | P8 | `topics` | Sudah | Buat, arsip, daftar |
| F-52 | Audit log | P8 | `audit_logs` | Sudah | Aksi utama tercatat |
| F-53 | Retensi & arsip | P8 | `meetings`, `audit_logs` | Belum | Fase 2/3 |

## 5. Traceability Aturan Bisnis

| Aturan | Implementasi | Status |
| --- | --- | --- |
| BR-01 Draf hanya pembuat/notulis/admin | `MeetingPolicy@update` | Sudah |
| BR-02 Finalisasi mengisi status/waktu/approver | `MeetingController@finalize` | Sudah |
| BR-03 Setelah final hanya admin | `MeetingPolicy@update` | Sudah |
| BR-04 Locked tidak dapat disunting | Enum tersedia; UI belum | Sebagian |
| BR-05 Visibilitas kerahasiaan | `Meeting::scopeVisibleTo` | Sudah |
| BR-06 Rahasia hanya peserta/admin | Scope + policy | Sudah |
| BR-07 Sinkronisasi tag dari mention | `MeetingTagSynchronizer` | Sudah |
| BR-08 Tag divisi mencakup anggota | Query Terkait Saya + `MeetingRecipientResolver` | Sudah |
| BR-09 PIC & tenggat sebelum final | Validasi finalisasi | Sudah |
| BR-10 Status tugas + overdue | Enum + accessor `is_overdue` | Sudah |
| BR-11 Hanya PIC/notulis/admin update | `ActionItemController@updateStatus` | Sudah |
| BR-12 Resume AI draft sampai disetujui | Enum `AiSummaryStatus` + UI approval | Sudah |
| BR-13 AI menghormati akses | `visibleTo` di `AiSummaryService` | Sudah |
| BR-14 Label AI + sumber | Label UI dan `sources`; tautan sumber belum ditampilkan | Sebagian |
| BR-15 Notifikasi tidak bocor | `MeetingRecipientResolver` + policy | Sudah |
| BR-16 Perubahan pengguna dicatat | Audit admin pengguna | Sudah |
| BR-17 Soft delete notulen | `SoftDeletes` | Sudah |

## 6. Traceability Proses → Data

| Proses | Tabel utama | Tabel pendukung |
| --- | --- | --- |
| P1 Perencanaan | `meetings`, `meeting_participants` | `meeting_templates`, `divisions`, `users` |
| P2 Pencatatan | `meetings`, `tags`, `decisions`, `action_items` | `topics`, `attachments` |
| P3 Resume AI | `ai_summaries` | `action_items`, `tags` |
| P4 Finalisasi | `meetings`, `audit_logs` | `action_items` |
| P5 Distribusi | `notifications` | `tags`, `meeting_participants`, `meetings` |
| P6 Tindak lanjut | `action_items`, `audit_logs` | `notifications`, `users`, `divisions` |
| P7 Pelaporan | `ai_summaries`, `meetings` | `action_items`, `decisions` |
| P8 Administrasi | `users`, `divisions`, `topics` | `audit_logs`, `notifications` |

## 7. Kesenjangan Prioritas

| Prioritas | Kesenjangan | Dampak | Rekomendasi |
| --- | --- | --- | --- |
| P1 | Meilisearch produksi | Pencarian masih memakai Scout driver database | Aktifkan Scout driver Meilisearch dan indeks |
| P1 | Ekspor PDF/Word | Distribusi dokumen final belum tersedia | Gunakan generator dokumen dan template ekspor |
| P1 | Penerapan saran tag AI | Saran AI belum bisa diterapkan langsung | Tambahkan aksi terima/tolak saran tag |
| P1 | Q&A dan rekap lintas notulen | Belum tersedia | Rilis fase berikutnya dengan sitasi sumber |
| P2 | Persistensi Yjs ke database | State kolaborasi masih disimpan di disk | Pindahkan snapshot Yjs ke `collab_documents` |
| P2 | SSO, kalender, chat | Integrasi perusahaan belum aktif | Sesuai kesiapan IdP dan kebijakan |
| P2 | Transkripsi, terjemahan, retensi | Fase lanjutan | Sesuai roadmap |

## 8. Catatan

- Status mencerminkan implementasi per 9 November 2026 pada Laravel 13.
- Perubahan requirement wajib memperbarui PRD, ERD, proses, dan matriks ini.
- Angka metrik target mengikuti PRD; pengukuran baseline dilakukan pada Fase 0.
