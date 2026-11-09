<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin()
            || Meeting::query()->visibleTo($user)->whereKey($meeting->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->canWriteMinutes();
    }

    public function update(User $user, Meeting $meeting): bool
    {
        if ($meeting->isLocked()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return ! $meeting->isFinal()
            && ($meeting->created_by === $user->id || $user->canWriteMinutes());
    }

    public function finalize(User $user, Meeting $meeting): bool
    {
        return ! $meeting->isLocked()
            && ($user->isAdmin() || $meeting->created_by === $user->id || $user->canWriteMinutes());
    }

    public function lock(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() && $meeting->isFinal();
    }

    public function unlock(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin() && $meeting->isLocked();
    }

    public function delete(User $user, Meeting $meeting): bool
    {
        return $user->isAdmin();
    }
}
