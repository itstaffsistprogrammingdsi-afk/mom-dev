<?php

namespace App\Services;

use App\Models\Division;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MeetingNumberGenerator
{
    public function generate(?Division $division = null, CarbonInterface|string|null $date = null): string
    {
        $date = match (true) {
            $date instanceof CarbonInterface => $date,
            is_string($date) && $date !== '' => Carbon::parse($date),
            default => now(),
        };

        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $initials = $division
            ? collect(preg_split('/\s+/', $division->name) ?: [])
                ->filter(fn (string $word) => preg_match('/[A-Za-z]/', $word) === 1)
                ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
                ->implode('')
            : 'UMUM';

        $initials = $initials !== '' ? $initials : 'UMUM';

        return sprintf('%02d/%s/%s/%s', $date->day, $romanMonths[$date->month - 1], $initials, $date->year);
    }
}
