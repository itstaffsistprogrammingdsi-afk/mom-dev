# Blueprint Sistem Notulensi (MoM) Terintegrasi AI

**Kode dokumen:** DOC-BLP-MOM-001  
**Versi:** 1.0  
**Tanggal:** 9 November 2026  
**Status:** Draft untuk persetujuan klien  
**Pemilik:** Wafiq Lana Pradana  
**Audiens:** Manajemen, Product Owner, Kepala Divisi, IT, QA

> Dokumen ini adalah ringkasan presentasi untuk klien. Isi teknis mendalam tetap mengacu pada PRD (01), ERD (02), Bisnis Proses (03), dan Matriks Traceability (04). Aspek komersial dan pembiayaan tidak dibahas dalam dokumen ini.

## 1. Ringkasan Eksekutif

Sistem Notulensi (MoM) adalah aplikasi web internal untuk mencatat, merangkum, mendistribusikan, dan menindaklanjuti hasil rapat lintas divisi.

Nilai utama sistem:

- Satu sumber kebenaran untuk notulen, keputusan, dan action item.
- Resume AI mempercepat pembacaan; manusia tetap memverifikasi sebelum notulen difinalkan.
- Tagging `@orang`, `@divisi`, dan `#topik` membuat distribusi, pencarian, dan halaman terkait berjalan otomatis.
- Action item memiliki PIC dan tenggat yang jelas, dengan pengingat dan pelacakan status.
- Hak akses berlapis, audit trail, dan label kerahasiaan menjaga informasi sensitif.

Inti penawaran untuk fase pertama adalah alur lengkap dari rapat sampai tindak lanjut: buat notulen, catat keputusan dan tugas, minta resume AI, verifikasi, finalkan, distribusikan, lalu pantau penyelesaian tugas.

## 2. Masalah Utama dan Solusi

| Masalah | Dampak | Solusi dalam sistem |
|---|---|---|
| Notulen tersebar di chat, email, dan dokumen pribadi | Sulit dicari dan rawan hilang | Pusat notulen, pencarian teks penuh, dan filter |
| PIC dan tenggat tidak jelas | Tindak lanjut rapat terlewat | Tabel action item wajib PIC dan tenggat sebelum final |
| Keputusan divisi lain tidak diketahui | Silo informasi dan pekerjaan duplikatif | Tagging divisi/topik dan halaman lintas divisi |
| Notulen panjang dan tidak seragam | Manajemen sulit membaca ringkasan | Template standar dan resume AI |
| Jejak akses dan perubahan tidak ada | Sulit diaudit dan dipertanggungjawabkan | Audit log, version history, dan label kerahasiaan |

## 3. Tujuan dan Metrik Keberhasilan

| Tujuan | Metrik | Target 6 bulan |
|---|---|---|
| Notulen tersentralisasi | Rapat resmi tercatat di sistem | >= 90% |
| Distribusi cepat | Waktu kirim setelah finalisasi | <= 24 jam, target segera |
| Tindak lanjut jelas | Action item memiliki PIC dan tenggat | 100% |
| Penyelesaian tepat waktu | Action item selesai sesuai tenggat | >= 80% |
| Efisiensi membaca | Notulen dengan resume AI yang dipakai | >= 70% |
| Kualitas AI | Resume disetujui tanpa koreksi besar | >= 80% |
| Kinerja | Waktu muat halaman dan pencarian | <= 2 detik dan <= 3 detik |

## 4. Peta Sistem

Alur utama:

```text
Perencanaan -> Pencatatan -> Resume AI -> Verifikasi -> Finalisasi
     -> Distribusi -> Tindak Lanjut -> Pelaporan dan Rekap
```

Arsitektur ringkas:

```text
+-------------------------------------+
| Browser Desktop / Mobile            |
| Vue 3 + Inertia + Tiptap Editor     |
+----------------+--------------------+
                 | HTTPS
+----------------v--------------------+      +------------------------+
| Laravel 13                          |<---->| Layanan AI (LLM API)   |
| Auth - Policy - Notulen - Tagging   |      | Resume - Ekstraksi     |
| Action Item - AI - Notifikasi       |      +------------------------+
+---+-------------+-------------+-----+
    |             |             |
+---v----+   +----v-----+   +---v--------------+
|Postgres|   |Queue dan |   |Object Storage    |
|        |   |Scheduler |   |Lampiran          |
+--------+   +----+-----+   +------------------+
                  |
             +----v-----+
             |Email/SMTP|
             +----------+

+-------------------------------------+
| Hocuspocus / Yjs                    |
| Kolaborasi real-time dan presence   |
+-------------------------------------+
```

## 5. Matriks Modul

| No | Modul | Fungsi Utama | Aktor | Rilis |
|---|---|---|---|---|
| 1 | Autentikasi dan Profil | Login, logout, reset password, 2FA, profil | Semua pengguna | R1 |
| 2 | Dashboard | Statistik, rapat mendatang, tugas saya, overdue | Semua pengguna | R1 |
| 3 | Notulen dan Editor | Buat, sunting, template, peserta, kerahasiaan, lampiran | Notulis, Admin | R1 |
| 4 | Tagging dan Pengetahuan | Mention orang/divisi/topik, halaman terkait | Notulis, pembaca | R1 |
| 5 | Pencarian dan Filter | Teks penuh, tanggal, divisi, topik, status | Semua pengguna | R1 |
| 6 | Action Item | Tabel, board, PIC, tenggat, status, overdue | PIC, Notulis, Admin | R1 |
| 7 | AI Assistant | Resume, keputusan, usulan tugas, saran tag | Notulis, Admin | R1 |
| 8 | Notifikasi dan Distribusi | In-app, email, pengingat | Semua penerima | R1 |
| 9 | Administrasi dan Audit | Pengguna, divisi, topik, template, audit | Admin | R1 |
| 10 | Kolaborasi Real-time | Dua notulis, presence, fallback | Notulis | R1 |
| 11 | Laporan dan Rekap | Rekap periodik, ekspor | Manajer, Admin | R2 dan R3 |
| 12 | Integrasi Perusahaan | SSO, kalender, chat, transkripsi | Admin, Manajer | R3 |

Keterangan rilis:

- **R1:** MVP dan inti P1, siap pilot 2-3 divisi.
- **R2:** penyempurnaan distribusi, pelaporan, dan ekspor.
- **R3:** integrasi lanjutan dan AI tingkat lanjut.

## 6. Matriks Fitur per Rilis

| ID | Fitur | Prioritas PRD | Rilis |
|---|---|---|---|
| F-01 | Editor dokumen kaya: heading, tabel, daftar, tautan, gambar | P0 | R1 |
| F-02 | Template notulen standar | P0 | R1 |
| F-03 | Template per jenis rapat | P1 | R1 |
| F-04 | Kolaborasi real-time dan riwayat versi | P0 | R1 |
| F-05 | Tabel action item | P0 | R1 |
| F-06 | Draf, final, dan locked | P0 | R1 |
| F-07 | Lampiran file dan tautan | P1 | R1 |
| F-08 | Ekspor PDF dan Word | P1 | R2 |
| F-10 | Tag orang `@nama` | P0 | R1 |
| F-11 | Tag divisi `@divisi` | P0 | R1 |
| F-12 | Tag topik `#topik` | P0 | R1 |
| F-13 | Tag pada level bagian dokumen | P1 | R2 |
| F-14 | Notifikasi dan halaman terkait | P0 | R1 |
| F-15 | Banyak tag sekaligus | P0 | R1 |
| F-16 | Tamu eksternal | P2 | R2 |
| F-20 | Halaman Terkait Saya | P0 | R1 |
| F-21 | Halaman topik | P0 | R1 |
| F-22 | Halaman divisi dan orang | P1 | R1 |
| F-23 | Pencarian teks penuh dan filter | P0 | R1 |
| F-24 | Board action item | P1 | R1 |
| F-25 | Dashboard ringkasan | P2 | R1 |
| F-30 | Notifikasi email dan in-app | P0 | R1 |
| F-31 | Pengingat H-3, H-1, dan terlambat | P1 | R1 |
| F-32 | Integrasi chat perusahaan | P2 | R3 |
| F-33 | Integrasi kalender | P1 | R3 |
| F-34 | Rekap mingguan otomatis | P1 | R3 |
| F-40 | Resume AI per notulen | P0 | R1 |
| F-41 | Ekstraksi action item | P0 | R1 |
| F-42 | Saran tag | P1 | R1 |
| F-43 | Resume lintas notulen | P1 | R3 |
| F-44 | Tanya-jawab atas notulen | P1 | R3 |
| F-45 | Resume per peran | P2 | R3 |
| F-46 | Transkripsi rekaman | P2 | R3 |
| F-47 | Penerjemahan resume | P2 | R3 |
| F-50 | Manajemen pengguna, divisi, dan peran | P0 | R1 |
| F-50 | SSO perusahaan | P0 | R3 |
| F-51 | Manajemen topik | P1 | R1 |
| F-52 | Audit log | P1 | R1 |
| F-53 | Retensi dan arsip | P2 | R3 |

Catatan: SSO ditempatkan di R3 karena bergantung pada kesiapan Identity Provider perusahaan. R1 memakai login lokal dan 2FA.

## 7. Matriks Peran dan Kerahasiaan

### 7.1 Hak Akses per Peran

| Fungsi | Admin | Notulis | Kepala Divisi | Peserta | Pembaca |
|---|---|---|---|---|---|
| Dashboard | Ya | Ya | Ya | Ya | Ya |
| Buat dan sunting draf | Ya | Ya | Tidak | Tidak | Tidak |
| Finalkan notulen | Ya | Ya | Tidak | Tidak | Tidak |
| Sunting setelah final | Ya | Tidak | Tidak | Tidak | Tidak |
| Lihat sesuai kerahasiaan | Ya | Ya | Ya | Ya | Ya |
| Update status tugas | Ya | Ya | Jika PIC | Jika PIC | Tidak |
| Kelola topik, divisi, pengguna | Ya | Tidak | Tidak | Tidak | Tidak |
| Audit log | Ya | Tidak | Tidak | Tidak | Tidak |
| Resume AI | Ya | Ya | Lihat | Lihat | Lihat |
| Unduh lampiran | Ya | Ya | Sesuai akses | Sesuai akses | Sesuai akses |

### 7.2 Tingkat Kerahasiaan

| Tingkat | Siapa yang dapat melihat | AI |
|---|---|---|
| Terbuka | Semua pengguna terautentikasi | Boleh, dengan label AI |
| Divisi | Peserta dan anggota divisi yang di-tag | Boleh untuk pengguna berhak |
| Terbatas | Peserta dan orang yang di-tag | Hanya jika penanya berhak |
| Rahasia | Peserta tertentu, pembuat, dan admin | Dikecualikan kecuali disetujui eksplisit |

## 8. Matriks Proses Bisnis

| Kode | Proses | Aktor Utama | Output | Modul | SLA |
|---|---|---|---|---|---|
| P1 | Perencanaan dan pembuatan notulen | Notulis | Draf, peserta, agenda | Notulen | <= 5 menit |
| P2 | Pencatatan rapat | Notulis | Isi notulen, tag, keputusan, tugas | Editor dan Tagging | Autosave <= 2 detik |
| P3 | Resume AI dan verifikasi | Notulis dan AI | Resume dan usulan tugas | AI | <= 60 detik |
| P4 | Finalisasi | Notulis | Notulen final dan versi terkunci | Notulen | <= 3 detik |
| P5 | Distribusi dan notifikasi | Sistem | Notifikasi in-app dan email | Notifikasi | <= 24 jam, target segera |
| P6 | Tindak lanjut | PIC dan Sistem | Status tugas dan pengingat | Action Item | Pengingat pukul 09.00 |
| P7 | Pelaporan dan rekap | Manajer dan AI | Rekap mingguan atau bulanan | Laporan | Senin pukul 08.00 |
| P8 | Administrasi dan audit | Admin | Pengguna, topik, divisi, audit | Admin | Audit <= 1 detik |

## 9. Blueprint Data

| Entitas | Fungsi | Relasi Kunci |
|---|---|---|
| `users` | Pengguna, peran, divisi, status aktif | Divisi 1 ke N Pengguna |
| `divisions` | Struktur divisi dan kepala divisi | Divisi 1 ke N Notulen |
| `topics` | Taksonomi topik lintas divisi | Topik 1 ke N Keputusan |
| `meeting_templates` | Template notulen per jenis rapat | Template 1 ke N Notulen |
| `meetings` | Notulen, isi dokumen, status, kerahasiaan | Pembuat 1 ke N Notulen |
| `meeting_participants` | Peserta internal/eksternal dan kehadiran | Notulen N ke N Pengguna |
| `tags` | Mention orang, divisi, dan topik | Notulen 1 ke N Tag |
| `decisions` | Keputusan rapat | Notulen 1 ke N Keputusan |
| `action_items` | Tugas, PIC, tenggat, dan status | Notulen 1 ke N Tugas |
| `ai_summaries` | Resume dan rekap AI | Notulen 1 ke N Resume |
| `attachments` | Berkas, tautan, dan rekaman | Notulen 1 ke N Lampiran |
| `audit_logs` | Jejak akses dan perubahan | Pengguna 1 ke N Audit |
| `notifications` | Notifikasi in-app per pengguna | Pengguna 1 ke N Notifikasi |
| `meeting_versions` | Riwayat versi dokumen | Notulen 1 ke N Versi |
| `collab_documents` | State kolaborasi Yjs | Notulen 1 ke 1 Dokumen kolaborasi |

## 10. Blueprint Arsitektur dan Teknologi

| Lapisan | Teknologi | Fungsi |
|---|---|---|
| Backend | Laravel 13 dan PHP 8.4 | Auth, policy, notulen, queue, scheduler |
| Frontend | Vue 3.5, TypeScript, Inertia 3 | Antarmuka SPA tanpa API terpisah |
| Build | Vite 8 | Kompilasi asset |
| UI | Tailwind 4 dan shadcn-vue | Komponen konsisten dan aksesibel |
| Editor | Tiptap 3 | Teks kaya, tabel, mention, lampiran |
| Kolaborasi | Yjs dan Hocuspocus 4 | Sinkronisasi real-time dan presence |
| Database | PostgreSQL 18 | JSONB, full-text search, trigram |
| Queue | Laravel Queue | AI, email, dan notifikasi asinkron |
| Scheduler | Laravel Scheduler | Pengingat dan rekap |
| AI | Provider-agnostik (OpenAI-compatible) | Resume, ekstraksi, dan saran tag |
| Email | SMTP | Distribusi dan pengingat |
| Storage | Local atau S3-compatible | Lampiran dan rekaman |
| Search | PostgreSQL FTS dan pg_trgm | Pencarian cepat tanpa layanan tambahan |

Pilihan ini menjaga sistem tetap sederhana untuk pilot, tetapi siap ditingkatkan ke Redis, Meilisearch, dan pgvector saat volume data bertambah.

## 11. Blueprint Halaman

| Halaman | Fungsi | Pengguna Utama |
|---|---|---|
| Login dan Reset Password | Autentikasi | Semua |
| Dashboard | Ringkasan aktivitas dan tugas | Semua |
| Daftar Notulen | Cari, filter, dan buka notulen | Semua |
| Buat Notulen | Pilih template dan isi awal | Notulis, Admin |
| Editor Notulen | Menulis, tag, action item, lampiran | Notulis, Admin |
| Detail Notulen | Baca, resume AI, keputusan, tugas | Semua sesuai akses |
| Terkait Saya | Notulen dan tugas yang menyebut saya | Semua |
| Halaman Topik | Notulen, keputusan, tugas per topik | Semua |
| Halaman Divisi | Aktivitas dan notulen divisi | Semua sesuai akses |
| Halaman Orang | Notulen dan tugas per orang | Semua sesuai akses |
| Tugas dan Board | Daftar dan kanban action item | PIC, Notulis, Admin |
| Notifikasi | Riwayat notifikasi | Semua |
| Admin Pengguna | Kelola akun, peran, dan divisi | Admin |
| Admin Divisi | Kelola struktur divisi | Admin |
| Admin Topik | Buat, gabung, dan arsipkan topik | Admin |
| Admin Template | Kelola template notulen | Admin |
| Admin Audit | Jejak akses dan perubahan | Admin |
| Pengaturan | Profil, keamanan, dan preferensi email | Semua |

## 12. Blueprint AI

### 12.1 Keluaran AI

| Keluaran | Isi | Tindakan Manusia |
|---|---|---|
| Resume notulen | Ringkasan, poin penting, keputusan | Notulis menyetujui atau menyunting |
| Ekstraksi action item | Tugas, PIC, tenggat | Notulis mengonfirmasi sebelum disimpan |
| Saran tag | Orang, divisi, topik | Notulis menerima atau menolak |
| Rekap lintas notulen | Per topik, divisi, atau periode | Manajer meninjau |
| Tanya-jawab | Jawaban dengan sitasi sumber | Pengguna membaca sumber |

### 12.2 Guardrail

| Guardrail | Implementasi |
|---|---|
| Manusia memverifikasi | Hasil AI berstatus draft sampai disetujui |
| Hak akses dihormati | AI hanya membaca notulen yang boleh diakses penanya |
| Transparan | Label "dibuat AI" dan tautan sumber |
| Data tidak untuk pelatihan | Penyedia AI wajib memiliki kebijakan no-training |
| Notulen sensitif | Notulen rahasia dikecualikan kecuali disetujui |
| Kegagalan layanan | Retry, fallback manual, dan notifikasi admin |
| Kuota | Pemantauan token dan kuota per divisi |

## 13. Matriks Notifikasi

| Event | Penerima | Kanal | Waktu | Syarat |
|---|---|---|---|---|
| Notulen difinalkan | Peserta, tag orang, anggota divisi tag | In-app dan email | Setelah final | Penerima berhak melihat |
| Tugas diberikan | PIC | In-app dan email | Setelah final | PIC terisi |
| Resume AI selesai | Notulis | In-app | Setelah job | Status draft |
| Pengingat H-3 | PIC | Email dan in-app | Pukul 09.00 | Tugas belum selesai |
| Pengingat H-1 | PIC | Email dan in-app | Pukul 09.00 | Tugas belum selesai |
| Terlambat | PIC dan kepala divisi | Email dan in-app | Harian | Melewati tenggat |
| Rekap mingguan | Pengguna dan kepala divisi | Email | Senin pukul 08.00 | Ada aktivitas |
| AI gagal | Notulis dan admin | In-app | Setelah retry gagal | Job gagal |

## 14. Kebutuhan Non-Fungsional

| Aspek | Kebutuhan |
|---|---|
| Kinerja | Halaman <= 2 detik; pencarian <= 3 detik; resume AI <= 60 detik |
| Ketersediaan | >= 99,5% pada jam kerja |
| Skalabilitas | Seluruh karyawan dan ribuan notulen per tahun |
| Kegunaan | Responsif desktop dan ponsel; bahasa Indonesia |
| Kolaborasi | Penyuntingan bersama tanpa konflik; riwayat versi |
| Keamanan | Policy di level query, enkripsi, audit, dan backup harian |
| Kepatuhan | Jejak audit dan retensi sesuai kebijakan perusahaan |
| Aksesibilitas | Kontras memadai, navigasi keyboard, dan label form |
| Observabilitas | Log terpusat, monitoring queue, dan alert kegagalan AI |

## 15. Rencana Rilis dan Deliverables

| Fase | Cakupan | Durasi Indikatif | Milestone |
|---|---|---|---|
| Fase 0 - Discovery | Wawancara divisi, contoh notulen, daftar divisi/topik, kebijakan data | 1 minggu | Peta proses disetujui |
| Fase 1 - MVP dan Pilot | Template, tagging, action item, notifikasi, resume AI, kolaborasi, pilot 2-3 divisi | 10 minggu | Go-live pilot |
| Fase 2 - Perluasan | Semua divisi, halaman divisi/orang, pengingat, board, ekspor | 7 minggu | Full rollout |
| Fase 3 - Penyempurnaan | SSO, kalender, chat, Q&A, rekap, transkripsi, evaluasi | 2 minggu | Evaluasi manfaat |

Deliverables:

- Aplikasi web siap pakai di lingkungan staging dan produksi.
- Database, seed demo, dan konfigurasi environment.
- Panduan pengguna dan panduan admin.
- Dokumen teknis dan matriks traceability yang diperbarui.
- Laporan UAT dan berita acara serah terima.
- Pelatihan notulis champion dan admin.

## 16. Kriteria Penerimaan (UAT)

### 16.1 Fungsional

- Login dan hak akses setiap peran berjalan sesuai matriks.
- Notulen dapat dibuat dari template, disunting, dan difinalkan.
- Mention `@orang`, `@divisi`, dan `#topik` tersimpan dan muncul di halaman terkait.
- Action item wajib memiliki PIC dan tenggat sebelum finalisasi.
- Resume AI dapat dibuat, disunting, disetujui, dan diterapkan ke action item.
- Notifikasi in-app dan email terkirim hanya kepada pihak yang berhak.
- Pencarian dan filter menampilkan hasil sesuai akses.
- PIC dapat memperbarui status tugas dan perubahan tercatat di audit.
- Dua notulis dapat menyunting bersamaan; fallback berjalan jika layanan kolaborasi mati.
- Admin dapat mengelola pengguna, divisi, topik, template, dan melihat audit.

### 16.2 KPI

- >= 90% rapat resmi tercatat.
- 100% action item memiliki PIC dan tenggat.
- >= 80% action item selesai tepat waktu.
- >= 70% notulen memiliki resume AI yang dipakai.
- Waktu muat <= 2 detik dan pencarian <= 3 detik.

## 17. Di Luar Lingkup

- Pengganti aplikasi video meeting atau kalender.
- Sistem manajemen proyek penuh.
- Transkripsi audio otomatis pada rilis awal.
- Aplikasi mobile native.
- Pengadaan perangkat, lisensi, dan infrastruktur.
- Migrasi seluruh arsip lama.
- Pembahasan komersial dan pembiayaan.

## 18. Risiko dan Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Notulis tidak disiplin mengisi | Data tidak lengkap | Template sederhana, target kepatuhan, dukungan manajemen |
| Resume AI keliru | Salah informasi | Verifikasi manusia, sitasi, dan label AI |
| Kebocoran data sensitif | Risiko hukum dan reputasi | Klasifikasi, policy, pengecualian AI, dan audit |
| Notifikasi berlebihan | Pengguna mengabaikan | Preferensi, rekap gabungan, dan batching |
| Adopsi rendah | Sistem tidak dipakai | Pilot, pelatihan, dan champion divisi |
| Ketergantungan layanan AI/Node | Fitur terganggu | Fallback, health check, dan runbook |

## 19. Pertanyaan Terbuka untuk Klien

1. Berapa jumlah divisi dan karyawan yang akan menggunakan sistem?
2. Apakah perusahaan memakai Google Workspace, Microsoft 365, atau keduanya?
3. Siapa yang menjadi notulis: sekretaris, anggota bergilir, atau siapa saja?
4. Apakah ada rapat dengan data sensitif yang tidak boleh diproses AI eksternal?
5. Penyedia AI mana yang diizinkan dan di mana data boleh diproses?
6. Saluran notifikasi utama: email, WhatsApp, atau chat perusahaan?
7. Apakah perlu dukungan notulen campuran Indonesia-Inggris?
8. Kebijakan retensi notulen dan rekaman?
9. Siapa admin yang ditunjuk untuk notulen rahasia?
10. Apakah kalender perusahaan menggunakan Google atau Microsoft?

## 20. Persetujuan

| Peran | Nama | Tanda Tangan / Tanggal |
|---|---|---|
| Pemilik Produk | Wafiq Lana Pradana | |
| Perwakilan Divisi | | |
| IT / Pengembang | | |
| Manajemen | | |

## Lampiran A - Glosarium

| Istilah | Arti |
|---|---|
| MoM | Minutes of Meeting atau notulen rapat |
| Notulis | Pengguna yang menulis dan memfinalkan notulen |
| Tag | Penanda orang, divisi, atau topik di dalam notulen |
| Mention | Tag yang disisipkan di editor |
| Action item | Tugas tindak lanjut dari rapat |
| PIC | Person in Charge |
| Resume AI | Ringkasan yang dihasilkan model bahasa |
| R1, R2, R3 | Tahap rilis pertama, kedua, dan ketiga |
| UAT | User Acceptance Test |
| SSO | Single Sign-On |

---

**Catatan dokumen:** Blueprint ini adalah ringkasan untuk presentasi klien. Perubahan requirement wajib memperbarui PRD, ERD, bisnis proses, matriks traceability, dan dokumen ini.
