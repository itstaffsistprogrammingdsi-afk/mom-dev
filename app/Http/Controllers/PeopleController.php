<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeopleController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        $viewer = $request->user();

        $meetings = Meeting::query()
            ->visibleTo($viewer)
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('participants', fn ($participant) => $participant->where('user_id', $user->id))
                    ->orWhereHas('tags', fn ($tag) => $tag->where('type', 'person')->where('target_id', $user->id));
            })
            ->select(['id', 'title', 'meeting_type', 'division_id', 'created_by', 'scheduled_at', 'status', 'confidentiality'])
            ->with(['division:id,name', 'creator:id,name'])
            ->orderByDesc('scheduled_at')
            ->limit(30)
            ->get();

        $tasks = ActionItem::query()
            ->visibleTo($viewer)
            ->where('pic_id', $user->id)
            ->with(['meeting:id,title', 'division:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit(30)
            ->get();

        return Inertia::render('People/Show', [
            'person' => $user->load('division:id,name', 'headedDivisions:id,name,head_id'),
            'meetings' => $meetings,
            'tasks' => $tasks,
        ]);
    }
}
