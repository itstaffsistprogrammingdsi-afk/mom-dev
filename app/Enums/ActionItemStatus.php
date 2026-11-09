<?php

namespace App\Enums;

enum ActionItemStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::InProgress => 'Berjalan',
            self::Done => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
