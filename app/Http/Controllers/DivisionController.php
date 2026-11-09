<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Division;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function show(Request $request, Division $division): Response
    {
        $user = $request->user();

        $meetings = Meeting::query()
            ->visibleTo($user)
            ->where(function ($query) use ($division) {
                $query->where('division_id', $division->id)
                    ->orWhereHas('tags', fn ($tag) => $tag->where('type', 'division')->where('target_id', $division->id));
            })
            ->select(['id', 'title', 'meeting_type', 'division_id', 'created_by', 'scheduled_at', 'status', 'confidentiality'])
            ->with(['division:id,name', 'creator:id,name'])
            ->orderByDesc('scheduled_at')
            ->limit(30)
            ->get();

        $tasks = ActionItem::query()
            ->visibleTo($user)
            ->where('division_id', $division->id)
            ->with(['meeting:id,title', 'pic:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit(30)
            ->get();

        return Inertia::render('Divisions/Show', [
            'division' => $division->load('head:id,name')->loadCount('users'),
            'meetings' => $meetings,
            'tasks' => $tasks,
        ]);
    }
}
