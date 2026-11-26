<?php

namespace App\Services\Notifications;

use App\Enums\Confidentiality;
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
        $meeting->loadMissing(['participants', 'tags', 'admins']);

        $participantIds = $meeting->participants->pluck('user_id')->filter();
        $personTagIds = $meeting->tags->where('type', TagType::Person)->pluck('target_id')->filter();
        $divisionTagIds = $meeting->tags->where('type', TagType::Division)->pluck('target_id')->filter();
        $adminIds = $meeting->admins->pluck('id')->filter();

        $userIds = $participantIds
            ->push($meeting->created_by)
            ->push($meeting->approved_by);

        if ($meeting->confidentiality === Confidentiality::Open) {
            $userIds = $userIds->merge($personTagIds);

            if ($divisionTagIds->isNotEmpty()) {
                $userIds = $userIds->merge(
                    User::query()->whereIn('division_id', $divisionTagIds)->pluck('id')
                );
            }
        } elseif ($meeting->confidentiality === Confidentiality::Division) {
            if ($divisionTagIds->isNotEmpty()) {
                $userIds = $userIds->merge(
                    User::query()->whereIn('division_id', $divisionTagIds)->pluck('id')
                );
            }
        } elseif ($meeting->confidentiality === Confidentiality::Restricted) {
            $userIds = $userIds->merge($personTagIds);
        } elseif ($meeting->confidentiality === Confidentiality::Secret) {
            $userIds = $userIds->merge($adminIds);
        }

        $candidates = User::query()
            ->whereIn('id', $userIds->filter()->unique())
            ->where('is_active', true)
            ->when($exclude, fn ($query) => $query->whereKeyNot($exclude->id))
            ->get();

        if ($meeting->confidentiality === Confidentiality::Open) {
            return $candidates->values();
        }

        return $candidates
            ->filter(fn (User $user) => ($user->isAdmin() && $adminIds->contains($user->id))
                || $user->id === $meeting->created_by
                || $participantIds->contains($user->id)
                || ($meeting->confidentiality === Confidentiality::Division
                    && $user->division_id
                    && $divisionTagIds->contains($user->division_id))
                || ($meeting->confidentiality === Confidentiality::Restricted
                    && $personTagIds->contains($user->id)))
            ->values();
    }
}
