<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Notulis = 'notulis';
    case Member = 'member';
    case Reader = 'reader';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Notulis => 'Notulis',
            self::Member => 'Anggota',
            self::Reader => 'Pembaca',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Akses penuh ke seluruh dokumen, pengguna, divisi, topik, template, dan audit log.',
            self::Notulis => 'Membuat dan menyunting draf, memfinalkan dokumen, mengelola action item, dan menjalankan AI.',
            self::Member => 'Membaca dokumen sesuai hak akses, memperbarui status tugas sebagai PIC, dan menerima notifikasi.',
            self::Reader => 'Membaca dokumen dan ringkasan sesuai hak akses tanpa menyunting atau mengubah tugas.',
        };
    }
}
