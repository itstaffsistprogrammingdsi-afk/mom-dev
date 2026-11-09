<?php

namespace App\Enums;

enum AiSummaryScope: string
{
    case Meeting = 'meeting';
    case Topic = 'topic';
    case Division = 'division';
    case Period = 'period';
    case Question = 'question';

    public function label(): string
    {
        return match ($this) {
            self::Meeting => 'Per Notulen',
            self::Topic => 'Per Topik',
            self::Division => 'Per Divisi',
            self::Period => 'Per Periode',
            self::Question => 'Tanya Jawab',
        };
    }
}
