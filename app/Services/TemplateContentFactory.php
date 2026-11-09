<?php

namespace App\Services;

use App\Enums\DocumentType;

class TemplateContentFactory
{
    /**
     * @return array<string, mixed>
     */
    public function default(DocumentType $type): array
    {
        $blocks = match ($type) {
            DocumentType::Meeting => [
                $this->note('Ganti teks panduan di bawah dengan hasil rapat yang sebenarnya.'),
                $this->heading('Agenda'),
                $this->paragraph('Tuliskan poin agenda rapat.'),
                $this->heading('Pembahasan'),
                $this->paragraph('Tuliskan pembahasan, data pendukung, dan kendala yang disampaikan peserta.'),
                $this->heading('Keputusan'),
                $this->bullets(['Keputusan pertama', 'Keputusan kedua']),
                $this->heading('Action Item'),
                $this->paragraph('Gunakan panel action item di halaman dokumen untuk menetapkan PIC dan tenggat.'),
            ],
            DocumentType::Proposal => [
                $this->note('Susun proposal secara ringkas: masalah, solusi, ruang lingkup, dan kebutuhan sumber daya.'),
                $this->heading('Ringkasan Proyek'),
                $this->paragraph('Jelaskan proyek dalam dua sampai empat kalimat.'),
                $this->heading('Latar Belakang'),
                $this->paragraph('Tuliskan konteks, masalah, dan alasan proyek ini diperlukan.'),
                $this->heading('Tujuan dan Manfaat'),
                $this->bullets(['Tujuan terukur pertama', 'Tujuan terukur kedua', 'Manfaat yang diharapkan']),
                $this->heading('Ruang Lingkup'),
                $this->paragraph('Batasan pekerjaan, deliverable, dan hal yang tidak termasuk.'),
                $this->heading('Timeline'),
                $this->bullets(['Fase 1: persiapan', 'Fase 2: pelaksanaan', 'Fase 3: evaluasi dan serah terima']),
                $this->heading('Anggaran'),
                $this->paragraph('Rincian kebutuhan biaya, sumber daya, dan asumsi.'),
                $this->heading('Risiko dan Mitigasi'),
                $this->bullets(['Risiko utama dan rencana mitigasi']),
                $this->heading('Penutup'),
                $this->paragraph('Ringkasan permintaan persetujuan atau langkah berikutnya.'),
            ],
            DocumentType::Brochure => [
                $this->note('Tulis materi promosi yang singkat, jelas, dan berorientasi manfaat.'),
                $this->heading('Headline'),
                $this->paragraph('Satu kalimat utama yang menjelaskan produk, layanan, atau program.'),
                $this->heading('Target Audiens'),
                $this->paragraph('Siapa yang paling membutuhkan produk atau program ini?'),
                $this->heading('Keunggulan'),
                $this->bullets(['Manfaat pertama', 'Manfaat kedua', 'Manfaat ketiga']),
                $this->heading('Call to Action'),
                $this->paragraph('Apa langkah berikutnya yang harus dilakukan pembaca?'),
                $this->heading('Kontak'),
                $this->paragraph('Nama, email, telepon, atau tautan yang dapat dihubungi.'),
            ],
            DocumentType::Report => [
                $this->note('Laporan harus dapat dibaca cepat: ringkasan di depan, detail di belakang.'),
                $this->heading('Ringkasan Eksekutif'),
                $this->paragraph('Tuliskan inti laporan dan keputusan yang perlu diambil.'),
                $this->heading('Latar Belakang'),
                $this->paragraph('Konteks, periode, dan cakupan laporan.'),
                $this->heading('Temuan'),
                $this->bullets(['Temuan utama pertama', 'Temuan utama kedua', 'Temuan utama ketiga']),
                $this->heading('Analisis'),
                $this->paragraph('Interpretasi data, penyebab, dan dampak.'),
                $this->heading('Rekomendasi'),
                $this->bullets(['Rekomendasi pertama beserta PIC', 'Rekomendasi kedua beserta tenggat']),
                $this->heading('Lampiran'),
                $this->paragraph('Daftar data pendukung, tabel, atau tautan referensi.'),
            ],
            DocumentType::Other => [
                $this->note('Gunakan struktur ini atau sesuaikan dengan kebutuhan dokumen.'),
                $this->heading('Judul Bagian'),
                $this->paragraph('Tuliskan isi dokumen di sini.'),
            ],
        };

        return [
            'type' => 'doc',
            'content' => $blocks,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $content
     * @return array<int, string>
     */
    public function sectionTitles(?array $content): array
    {
        $titles = [];

        foreach ($content['content'] ?? [] as $node) {
            if (($node['type'] ?? null) === 'heading') {
                $text = $node['content'][0]['text'] ?? null;

                if (is_string($text) && $text !== '') {
                    $titles[] = $text;
                }
            }
        }

        return $titles;
    }

    /**
     * @return array<string, mixed>
     */
    private function heading(string $text): array
    {
        return [
            'type' => 'heading',
            'attrs' => ['level' => 2],
            'content' => [['type' => 'text', 'text' => $text]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paragraph(string $text = ''): array
    {
        return [
            'type' => 'paragraph',
            'content' => $text === '' ? [] : [['type' => 'text', 'text' => $text]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function note(string $text): array
    {
        return [
            'type' => 'paragraph',
            'content' => [[
                'type' => 'text',
                'marks' => [['type' => 'italic']],
                'text' => $text,
            ]],
        ];
    }

    /**
     * @param  array<int, string>  $items
     * @return array<string, mixed>
     */
    private function bullets(array $items): array
    {
        return [
            'type' => 'bulletList',
            'content' => array_map(fn (string $item) => [
                'type' => 'listItem',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [['type' => 'text', 'text' => $item]],
                ]],
            ], $items),
        ];
    }
}
