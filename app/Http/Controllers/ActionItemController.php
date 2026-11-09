<?php

namespace App\Http\Controllers;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\ActionItemAssignedNotification;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ActionItemController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status', 'pic', 'division', 'overdue']);

        $query = ActionItem::query()
            ->visibleTo($user)
            ->filter($filters)
            ->with(['meeting:id,title,scheduled_at', 'pic:id,name', 'division:id,name'])
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date');

        $items = $query->paginate(20)->withQueryString();

        $base = ActionItem::query()->visibleTo($user);
        $view = $request->string('view')->toString() === 'board' ? 'board' : 'list';

        $board = null;

        if ($view === 'board') {
            $board = ActionItem::query()
                ->visibleTo($user)
                ->filter($filters)
                ->open()
                ->with(['meeting:id,title,scheduled_at', 'pic:id,name', 'division:id,name'])
                ->orderByRaw('due_date IS NULL')
                ->orderBy('due_date')
                ->limit(200)
                ->get()
                ->groupBy(fn (ActionItem $item) => $item->status->value)
                ->map(fn ($group) => $group->values())
                ->all();
        }

        return Inertia::render('ActionItems/Index', [
            'items' => $items,
            'board' => $board,
            'view' => $view,
            'filters' => $filters,
            'stats' => [
                'total' => (clone $base)->count(),
                'open' => (clone $base)->open()->count(),
                'overdue' => (clone $base)->open()->whereDate('due_date', '<', today())->count(),
                'done' => (clone $base)->where('status', ActionItemStatus::Done->value)->count(),
            ],
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'canUpdate' => $user->canWriteMinutes(),
        ]);
    }

    public function store(Request $request, Meeting $meeting, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $this->validatePayload($request);
        $item = $meeting->actionItems()->create([
            ...$validated,
            'source' => 'manual',
            'ai_confirmed' => true,
        ]);

        $audit->log('action_item.created', $item, 'Menambahkan action item', meetingId: $meeting->id);
        $this->notifyAssignee($item);

        return back()->with('success', 'Action item ditambahkan.');
    }

    public function update(Request $request, ActionItem $actionItem, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $actionItem->meeting);

        $previousPic = $actionItem->pic_id;
        $validated = $this->validatePayload($request);
        $actionItem->update($validated);

        $audit->log('action_item.updated', $actionItem, 'Memperbarui action item', meetingId: $actionItem->meeting_id);

        if ($actionItem->pic_id && $actionItem->pic_id !== $previousPic) {
            $this->notifyAssignee($actionItem);
        }

        return back()->with('success', 'Action item diperbarui.');
    }

    public function destroy(Request $request, ActionItem $actionItem, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $actionItem->meeting);

        $meetingId = $actionItem->meeting_id;
        $actionItem->delete();

        $audit->log('action_item.deleted', null, 'Menghapus action item', meetingId: $meetingId);

        return back()->with('success', 'Action item dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pic_id' => ['nullable', 'exists:users,id'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::enum(ActionItemStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function notifyAssignee(ActionItem $actionItem): void
    {
        if (! $actionItem->pic_id) {
            return;
        }

        $actionItem->loadMissing('meeting', 'pic');

        $actionItem->pic?->notify(new ActionItemAssignedNotification($actionItem));
    }

    public function updateStatus(
        Request $request,
        ActionItem $actionItem,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('view', $actionItem->meeting);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(ActionItemStatus::class)],
        ]);

        $status = ActionItemStatus::from($validated['status']);
        $isMine = $actionItem->pic_id === $request->user()->id;

        abort_unless($request->user()->canWriteMinutes() || $isMine, 403);

        $actionItem->update([
            'status' => $status,
            'completed_at' => $status === ActionItemStatus::Done ? now() : null,
        ]);

        $audit->log(
            'action_item.status_updated',
            $actionItem,
            "Status tugas diubah menjadi {$status->label()}",
            ['status' => $status->value],
            $actionItem->meeting_id,
        );

        return back()->with('success', 'Status action item diperbarui.');
    }
}
