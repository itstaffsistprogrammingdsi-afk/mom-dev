<?php

namespace App\Services\Notifications;

use App\Enums\TagType;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;

class MeetingRecipientResolver
{
    /**
     * @return Collection<int, User>
     */
    public function resolve(Meeting $meeting, ?User $exclude = null): Collection
    {
        $meeting->loadMissing(['participants', 'tags']);

        $userIds = collect();

        $userIds = $userIds
            ->merge($meeting->participants->pluck('user_id'))
            ->merge($meeting->tags->where('type', TagType::Person)->pluck('target_id'))
            ->push($meeting->created_by)
            ->push($meeting->approved_by);

        $divisionIds = $meeting->tags->where('type', TagType::Division)->pluck('target_id')->filter()->values();

        if ($divisionIds->isNotEmpty()) {
            $userIds = $userIds->merge(
                User::query()->whereIn('division_id', $divisionIds)->pluck('id')
            );
        }

        $candidates = User::query()
            ->whereIn('id', $userIds->filter()->unique())
            ->where('is_active', true)
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude->id))
            ->get();

        return $candidates
            ->filter(fn (User $user) => Meeting::query()->visibleTo($user)->whereKey($meeting->id)->exists())
            ->values();
    }
}
