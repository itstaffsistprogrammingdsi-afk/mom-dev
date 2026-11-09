<?php

namespace App\Enums;

enum TagType: string
{
    case Person = 'person';
    case Division = 'division';
    case Topic = 'topic';

    public function label(): string
    {
        return match ($this) {
            self::Person => 'Orang',
            self::Division => 'Divisi',
            self::Topic => 'Topik',
        };
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Person, self::Division => '@',
            self::Topic => '#',
        };
    }
}
