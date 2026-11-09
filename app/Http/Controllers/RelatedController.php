<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Meeting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RelatedController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $meetings = Meeting::query()
            ->visibleTo($user)
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('participants', fn ($participant) => $participant->where('user_id', $user->id))
                    ->orWhereHas('tags', function ($tag) use ($user) {
                        $tag->where(function ($inner) use ($user) {
                            $inner->where('type', 'person')->where('target_id', $user->id);

                            if ($user->division_id) {
                                $inner->orWhere(fn ($division) => $division
                                    ->where('type', 'division')
                                    ->where('target_id', $user->division_id));
                            }
                        });
                    });
            })
            ->select(['id', 'title', 'meeting_type', 'division_id', 'created_by', 'scheduled_at', 'status', 'confidentiality'])
            ->with(['division:id,name', 'creator:id,name'])
            ->orderByDesc('scheduled_at')
            ->limit(30)
            ->get();

        $tasks = ActionItem::query()
            ->visibleTo($user)
            ->where(function ($query) use ($user) {
                $query->where('pic_id', $user->id);

                if ($user->division_id) {
                    $query->orWhere('division_id', $user->division_id);
                }
            })
            ->with(['meeting:id,title', 'pic:id,name', 'division:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit(50)
            ->get()
            ->values();

        return Inertia::render('Related/Index', [
            'meetings' => $meetings,
            'tasks' => $tasks,
            'summary' => [
                'meetings' => $meetings->count(),
                'open_tasks' => $tasks->filter(
                    fn (ActionItem $task) => in_array($task->status->value, ['new', 'in_progress'], true)
                )->count(),
                'overdue_tasks' => $tasks->filter(fn (ActionItem $task) => $task->is_overdue)->count(),
            ],
        ]);
    }
}
