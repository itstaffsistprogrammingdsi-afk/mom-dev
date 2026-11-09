<?php

namespace App\Enums;

enum Confidentiality: string
{
    case Open = 'open';
    case Division = 'division';
    case Restricted = 'restricted';
    case Secret = 'secret';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Terbuka',
            self::Division => 'Divisi',
            self::Restricted => 'Terbatas',
            self::Secret => 'Rahasia',
        };
    }
}
