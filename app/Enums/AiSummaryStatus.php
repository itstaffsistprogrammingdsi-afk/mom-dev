<?php

namespace App\Enums;

enum AiSummaryStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf AI',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
