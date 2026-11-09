<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Topic;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $topics = Topic::query()
            ->where('status', 'active')
            ->withCount([
                'meetings' => fn ($query) => $query->visibleTo($user),
                'decisions',
            ])
            ->orderBy('name')
            ->get();

        return Inertia::render('Topics/Index', [
            'topics' => $topics,
        ]);
    }

    public function show(Request $request, Topic $topic): Response
    {
        $user = $request->user();

        $meetings = Meeting::query()
            ->visibleTo($user)
            ->whereHas('tags', fn ($tag) => $tag->where('type', 'topic')->where('target_id', $topic->id))
            ->select(['id', 'title', 'meeting_type', 'division_id', 'scheduled_at', 'status', 'confidentiality'])
            ->with('division:id,name')
            ->orderByDesc('scheduled_at')
            ->limit(50)
            ->get();

        $decisions = $topic->decisions()
            ->with('meeting:id,title,scheduled_at,confidentiality')
            ->latest('decided_at')
            ->limit(50)
            ->get();

        $summaries = $topic->summaries()
            ->where('status', 'approved')
            ->latest('version')
            ->limit(5)
            ->get();

        return Inertia::render('Topics/Show', [
            'topic' => $topic,
            'meetings' => $meetings,
            'decisions' => $decisions,
            'summaries' => $summaries,
        ]);
    }
}
