<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

class MockAiClient implements AiClient
{
    public function summarizeMeeting(array $payload): array
    {
        $text = trim((string) ($payload['content_text'] ?? ''));
        $sentences = collect(preg_split('/(?<=[.!?])\s+/u', $text) ?: [])
            ->map(fn (string $sentence) => trim($sentence))
            ->filter()
            ->values();

        $summary = $sentences->take(3)->implode(' ');

        if ($summary === '') {
            $summary = 'Draf ringkasan belum tersedia karena isi notulen masih kosong.';
        }

        $points = $sentences->take(5)->values()->all();

        $decisions = collect($points)
            ->filter(fn (string $point) => Str::contains(Str::lower($point), ['sepakat', 'menyepakati', 'diputuskan', 'keputusan', 'disetujui']))
            ->values()
            ->all();

        $actionItems = collect($points)
            ->filter(fn (string $point) => Str::contains(Str::lower($point), ['akan', 'perlu', 'harus', 'tindak lanjut', 'selesai', 'susun', 'siapkan']))
            ->take(3)
            ->map(fn (string $point) => [
                'title' => Str::limit($point, 120, ''),
                'description' => null,
                'pic_name' => null,
                'division_name' => null,
                'due_date' => null,
            ])
            ->values()
            ->all();

        if ($actionItems === []) {
            $actionItems = [[
                'title' => 'Tindak lanjuti hasil rapat: '.Str::limit((string) ($payload['title'] ?? 'rapat'), 80, ''),
                'description' => 'Perlu dikonfirmasi oleh notulis.',
                'pic_name' => null,
                'division_name' => null,
                'due_date' => null,
            ]];
        }

        return [
            'ringkasan' => $summary,
            'poin' => $points,
            'keputusan' => $decisions,
            'action_items' => $actionItems,
            'saran_tag' => [
                'orang' => [],
                'divisi' => [],
                'topik' => [],
            ],
        ];
    }
}
