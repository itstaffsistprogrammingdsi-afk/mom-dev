<?php

namespace App\Observers;

use App\Models\Tag;
use App\Services\MeetingAccessProjector;

class TagObserver
{
    public function __construct(private readonly MeetingAccessProjector $projector) {}

    public function saved(Tag $tag): void
    {
        $this->sync($tag);
    }

    public function deleted(Tag $tag): void
    {
        $this->sync($tag);
    }

    private function sync(Tag $tag): void
    {
        $meeting = $tag->meeting()->first();

        if ($meeting) {
            $this->projector->sync($meeting);
        }
    }
}
