<?php

namespace App\Enums;

enum ActionItemFeedbackStatus: string
{
    case Acknowledged = 'acknowledged';
    case Scheduled = 'scheduled';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';
    case NeedHelp = 'need_help';

    public function label(): string
    {
        return match ($this) {
            self::Acknowledged => 'Diterima',
            self::Scheduled => 'Akan diagendakan',
            self::InProgress => 'Sedang dikerjakan',
            self::Blocked => 'Terhambat',
            self::Done => 'Selesai',
            self::NeedHelp => 'Butuh bantuan',
        };
    }
}
