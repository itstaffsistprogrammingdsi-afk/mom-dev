<?php

namespace App\Observers;

use App\Models\Meeting;
use App\Services\MeetingAccessProjector;

class MeetingObserver
{
    public function __construct(private readonly MeetingAccessProjector $projector) {}

    public function saved(Meeting $meeting): void
    {
        if ($meeting->wasRecentlyCreated || $meeting->wasChanged(['confidentiality', 'division_id'])) {
            $this->projector->sync($meeting);
        }
    }

    public function restored(Meeting $meeting): void
    {
        $this->projector->sync($meeting);
    }
}
