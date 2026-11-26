<?php

namespace App\Http\Controllers;

use App\Enums\MeetingStatus;
use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $meetings = Meeting::query()->visibleTo($user);

        $stats = Cache::remember("dashboard:stats:{$user->id}", now()->addSeconds(30), function () use ($meetings, $user) {
            return [
                'meetings_this_month' => (clone $meetings)
                    ->whereBetween('scheduled_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->count(),
                'open_tasks' => ActionItem::query()->visibleTo($user)->open()->count(),
                'overdue_tasks' => ActionItem::query()->visibleTo($user)->open()
                    ->whereDate('due_date', '<', today())
                    ->count(),
                'active_topics' => Topic::query()->where('status', 'active')->count(),
            ];
        });

        $upcoming = (clone $meetings)
            ->select(['id', 'title', 'document_type', 'meeting_type', 'scheduled_at', 'status', 'confidentiality', 'division_id'])
            ->with('division:id,name')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at')
            ->limit(5)
            ->get();

        $recent = (clone $meetings)
            ->select(['id', 'title', 'document_type', 'meeting_type', 'scheduled_at', 'status', 'confidentiality', 'division_id', 'created_at'])
            ->with('division:id,name')
            ->latest()
            ->limit(5)
            ->get();

        $myTasks = ActionItem::query()
            ->visibleTo($user)
            ->open()
            ->where('pic_id', $user->id)
            ->with(['meeting:id,title', 'division:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $drafts = $user->canWriteMinutes()
            ? Meeting::query()
                ->visibleTo($user)
                ->where('created_by', $user->id)
                ->where('status', MeetingStatus::Draft->value)
                ->select(['id', 'title', 'document_type', 'meeting_type', 'scheduled_at', 'status', 'confidentiality', 'division_id', 'updated_at'])
                ->with('division:id,name')
                ->latest('updated_at')
                ->limit(5)
                ->get()
            : collect();

        $divisionTasks = $user->division_id
            ? ActionItem::query()
                ->visibleTo($user)
                ->open()
                ->where('division_id', $user->division_id)
                ->with(['meeting:id,title', 'pic:id,name'])
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->limit(5)
                ->get()
            : collect();

        $approvals = Meeting::query()
            ->visibleTo($user)
            ->where('approver_id', $user->id)
            ->where('status', MeetingStatus::Final->value)
            ->whereDoesntHave('signatures', fn ($query) => $query->where('role', 'approver'))
            ->select(['id', 'title', 'document_type', 'meeting_type', 'scheduled_at', 'status', 'confidentiality', 'division_id', 'finalized_at'])
            ->with('division:id,name')
            ->latest('finalized_at')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'myTasks' => $myTasks,
            'drafts' => $drafts,
            'divisionTasks' => $divisionTasks,
            'approvals' => $approvals,
        ]);
    }
}
