<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Invited = 'invited';
    case Present = 'present';
    case Absent = 'absent';
    case Excused = 'excused';

    public function label(): string
    {
        return match ($this) {
            self::Invited => 'Diundang',
            self::Present => 'Hadir',
            self::Absent => 'Tidak Hadir',
            self::Excused => 'Izin',
        };
    }
}
