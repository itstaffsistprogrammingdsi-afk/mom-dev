<?php

namespace App\Policies;

use App\Models\QuickNote;
use App\Models\User;

class QuickNotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, QuickNote $quickNote): bool
    {
        if ($user->isAdmin() || $quickNote->user_id === $user->id) {
            return true;
        }

        return $quickNote->mentions()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, QuickNote $quickNote): bool
    {
        return $user->isAdmin() || $quickNote->user_id === $user->id;
    }

    public function delete(User $user, QuickNote $quickNote): bool
    {
        return $this->update($user, $quickNote);
    }

    public function respond(User $user, QuickNote $quickNote): bool
    {
        return $quickNote->mentions()->where('user_id', $user->id)->exists();
    }
}
