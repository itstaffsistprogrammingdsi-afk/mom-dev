<?php

namespace App\Services;

use App\Enums\Confidentiality;
use App\Enums\TagType;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MeetingAccessProjector
{
    public function sync(Meeting $meeting): void
    {
        DB::table('meeting_access')->where('meeting_id', $meeting->id)->delete();

        if (! in_array($meeting->confidentiality, [Confidentiality::Division, Confidentiality::Restricted], true)) {
            return;
        }

        $tags = $meeting->tags()->get(['type', 'target_id']);

        if ($meeting->confidentiality === Confidentiality::Division) {
            $divisionIds = $tags->where('type', TagType::Division)->pluck('target_id')->filter()->unique();

            if ($divisionIds->isEmpty()) {
                return;
            }

            $userIds = User::query()
                ->whereIn('division_id', $divisionIds)
                ->where('is_active', true)
                ->pluck('id');

            $this->insert($meeting->id, $userIds->all(), 'division');

            return;
        }

        $personIds = $tags->where('type', TagType::Person)->pluck('target_id')->filter()->unique();

        if ($personIds->isEmpty()) {
            return;
        }

        $userIds = User::query()
            ->whereIn('id', $personIds)
            ->where('is_active', true)
            ->pluck('id');

        $this->insert($meeting->id, $userIds->all(), 'person');
    }

    public function rebuildForUser(User $user): void
    {
        DB::table('meeting_access')->where('user_id', $user->id)->delete();

        if (! $user->is_active) {
            return;
        }

        $meetings = Meeting::query()
            ->where(function ($query) use ($user) {
                if ($user->division_id) {
                    $query->orWhere(function ($division) use ($user) {
                        $division->where('confidentiality', Confidentiality::Division->value)
                            ->whereHas('tags', fn ($tag) => $tag
                                ->where('type', TagType::Division->value)
                                ->where('target_id', $user->division_id));
                    });
                }

                $query->orWhere(function ($person) use ($user) {
                    $person->where('confidentiality', Confidentiality::Restricted->value)
                        ->whereHas('tags', fn ($tag) => $tag
                            ->where('type', TagType::Person->value)
                            ->where('target_id', $user->id));
                });
            })
            ->pluck('id');

        $source = $user->division_id ? 'division' : 'person';

        $this->insertMany($meetings->all(), [$user->id], $source);
    }

    /**
     * @param  array<int, int>  $userIds
     */
    private function insert(int $meetingId, array $userIds, string $source): void
    {
        $this->insertMany([$meetingId], $userIds, $source);
    }

    /**
     * @param  array<int, int>  $meetingIds
     * @param  array<int, int>  $userIds
     */
    private function insertMany(array $meetingIds, array $userIds, string $source): void
    {
        if ($meetingIds === [] || $userIds === []) {
            return;
        }

        $rows = [];

        foreach ($meetingIds as $meetingId) {
            foreach ($userIds as $userId) {
                $rows[] = [
                    'meeting_id' => $meetingId,
                    'user_id' => $userId,
                    'source' => $source,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('meeting_access')->insertOrIgnore($chunk);
        }
    }
}
