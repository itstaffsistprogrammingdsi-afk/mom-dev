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
}
