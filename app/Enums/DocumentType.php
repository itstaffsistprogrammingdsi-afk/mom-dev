<?php

namespace App\Enums;

enum DocumentType: string
{
    case Meeting = 'meeting';
    case Proposal = 'proposal';
    case Brochure = 'brochure';
    case Report = 'report';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Meeting => 'Notulen',
            self::Proposal => 'Proposal',
            self::Brochure => 'Brosur',
            self::Report => 'Laporan',
            self::Other => 'Dokumen Lain',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Meeting => 'Catatan rapat, keputusan, dan action item.',
            self::Proposal => 'Usulan proyek dengan tujuan, ruang lingkup, timeline, dan anggaran.',
            self::Brochure => 'Materi promosi singkat untuk produk, layanan, atau program.',
            self::Report => 'Laporan berkala dengan temuan, analisis, dan rekomendasi.',
            self::Other => 'Dokumen bebas dengan struktur yang disesuaikan.',
        };
    }
}
