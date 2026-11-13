<?php

namespace App\Services;

use App\Models\QuickNote;
use App\Models\QuickNoteMention;
use App\Models\User;

class QuickNoteMentionSynchronizer
{
    public function __construct(private readonly MeetingContentParser $parser) {}

    /**
     * @param  array<int, int>  $hoursByUser
     * @return array<int, QuickNoteMention>
     */
    public function sync(QuickNote $quickNote, ?array $content, array $hoursByUser = []): array
    {
        $mentions = collect($this->parser->parse($content)['mentions'])
            ->where('type', 'person')
            ->unique('id');

        $existing = $quickNote->mentions()->get()->keyBy('user_id');
        $keptUserIds = [];
        $created = [];

        foreach ($mentions as $mention) {
            $userId = (int) $mention['id'];

            if (! User::query()->whereKey($userId)->where('is_active', true)->exists()) {
                continue;
            }

            $record = $existing->get($userId);
            $hours = (int) ($hoursByUser[$userId] ?? $record?->response_hours ?? 24);

            if (! in_array($hours, [1, 4, 24, 48], true)) {
                $hours = 24;
            }

            if ($record) {
                if ($record->responded_at === null) {
                    $record->update([
                        'response_hours' => $hours,
                        'due_at' => $record->created_at->copy()->addHours($hours),
                    ]);
                }
            } else {
                $created[] = $quickNote->mentions()->create([
                    'user_id' => $userId,
                    'response_hours' => $hours,
                    'due_at' => now()->addHours($hours),
                ]);
            }

            $keptUserIds[] = $userId;
        }

        if ($keptUserIds === []) {
            $quickNote->mentions()->delete();
        } else {
            $quickNote->mentions()->whereNotIn('user_id', $keptUserIds)->delete();
        }

        return $created;
    }
}
