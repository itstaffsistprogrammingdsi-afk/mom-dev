<?php

namespace App\Enums;

enum MeetingStatus: string
{
    case Draft = 'draft';
    case Final = 'final';
    case Locked = 'locked';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Final => 'Final',
            self::Locked => 'Terkunci',
        };
    }
}
