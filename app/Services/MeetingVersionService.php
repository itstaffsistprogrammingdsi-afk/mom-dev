<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingVersion;

class MeetingVersionService
{
    public function snapshot(Meeting $meeting, string $reason = 'snapshot', ?int $userId = null): MeetingVersion
    {
        $version = (int) $meeting->versions()->max('version') + 1;

        $snapshot = $meeting->versions()->create([
            'version' => $version,
            'content' => $meeting->content,
            'content_text' => $meeting->content_text,
            'reason' => $reason,
            'created_by' => $userId,
        ]);

        $meeting->forceFill(['version' => $version])->save();

        return $snapshot;
    }
}
