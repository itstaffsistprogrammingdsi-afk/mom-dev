<?php

namespace App\Services;

use App\Models\Meeting;

class MeetingTagSynchronizer
{
    public function __construct(private readonly MeetingContentParser $parser) {}

    public function sync(Meeting $meeting, ?array $content, ?int $userId = null): void
    {
        $mentions = $this->parser->parse($content)['mentions'];

        $meeting->tags()->delete();

        foreach ($mentions as $mention) {
            $meeting->tags()->create([
                'type' => $mention['type'],
                'target_id' => $mention['id'],
                'target_name' => $mention['label'],
                'created_by' => $userId,
            ]);
        }
    }
}
