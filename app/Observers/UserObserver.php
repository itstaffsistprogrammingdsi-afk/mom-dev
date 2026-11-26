<?php

namespace App\Observers;

use App\Models\User;
use App\Services\MeetingAccessProjector;

class UserObserver
{
    public function __construct(private readonly MeetingAccessProjector $projector) {}

    public function saved(User $user): void
    {
        if ($user->wasRecentlyCreated || $user->wasChanged(['division_id', 'is_active'])) {
            $this->projector->rebuildForUser($user);
        }
    }
}
