<?php

namespace App\Enums;

enum MeetingType: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Division = 'division';
    case CrossDivision = 'cross_division';
    case Management = 'management';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Rapat Harian',
            self::Weekly => 'Rapat Mingguan',
            self::Division => 'Rapat Divisi',
            self::CrossDivision => 'Rapat Lintas Divisi',
            self::Management => 'Rapat Manajemen',
            self::Other => 'Lainnya',
        };
    }
}
