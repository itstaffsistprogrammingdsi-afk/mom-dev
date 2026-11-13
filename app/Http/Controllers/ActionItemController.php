<?php

namespace App\Http\Controllers;

use App\Enums\ActionItemFeedbackStatus;
use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Models\ActionItemFeedback;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\ActionItemAssignedNotification;
use App\Notifications\ActionItemResponseNotification;
use App\Services\AuditLogger;
use App\Services\Notifications\MeetingRecipientResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActionItemController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['search', 'status', 'pic', 'division', 'overdue', 'owner']);

        $query = ActionItem::query()
            ->visibleTo($user)
            ->filter($filters)
            ->with(['meeting:id,title,scheduled_at', 'pic:id,name', 'division:id,name', 'feedback.user:id,name'])
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
                ->with(['meeting:id,title,scheduled_at', 'pic:id,name', 'division:id,name', 'feedback.user:id,name'])
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
        $status = ActionItemStatus::from($validated['status'] ?? ActionItemStatus::New->value);
        $responseHours = (int) ($validated['response_hours'] ?? 24);
        $item = $meeting->actionItems()->create([
            ...$validated,
            'status' => $status,
            'response_hours' => $responseHours,
            'response_due_at' => $status === ActionItemStatus::InProgress ? now()->addHours($responseHours) : null,
            'completed_at' => $status === ActionItemStatus::Done ? now() : null,
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
        $status = isset($validated['status'])
            ? ActionItemStatus::from($validated['status'])
            : $actionItem->status;
        $responseHours = (int) ($validated['response_hours'] ?? $actionItem->response_hours ?? 24);

        $attributes = [
            ...$validated,
            'response_hours' => $responseHours,
            'completed_at' => $status === ActionItemStatus::Done ? ($actionItem->completed_at ?? now()) : null,
        ];

        if ($status === ActionItemStatus::InProgress && $actionItem->response_due_at === null) {
            $attributes['response_due_at'] = now()->addHours($responseHours);
        }

        $actionItem->update($attributes);

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
            'response_hours' => ['nullable', 'integer', 'in:1,4,24,48'],
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
            'response_hours' => ['nullable', 'integer', 'in:1,4,24,48'],
        ]);

        $status = ActionItemStatus::from($validated['status']);
        $isMine = $actionItem->pic_id === $request->user()->id;

        abort_unless($request->user()->canWriteMinutes() || $isMine, 403);

        $responseHours = (int) ($validated['response_hours'] ?? $actionItem->response_hours ?? 24);

        $attributes = [
            'status' => $status,
            'response_hours' => $responseHours,
            'completed_at' => $status === ActionItemStatus::Done ? now() : null,
        ];

        if ($status === ActionItemStatus::InProgress && $actionItem->response_due_at === null) {
            $attributes['response_due_at'] = now()->addHours($responseHours);
        }

        if ($isMine && ! $actionItem->first_responded_at) {
            $attributes['first_responded_at'] = now();
        }

        $actionItem->update($attributes);

        $audit->log(
            'action_item.status_updated',
            $actionItem,
            "Status tugas diubah menjadi {$status->label()}",
            ['status' => $status->value],
            $actionItem->meeting_id,
        );

        return back()->with('success', 'Status action item diperbarui.');
    }

    public function storeFeedback(
        Request $request,
        ActionItem $actionItem,
        AuditLogger $audit,
        MeetingRecipientResolver $recipients,
    ): RedirectResponse {
        $this->authorize('view', $actionItem->meeting);

        $isMine = $actionItem->pic_id === $request->user()->id;

        abort_unless($request->user()->canWriteMinutes() || $isMine, 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'response_status' => ['required', Rule::enum(ActionItemFeedbackStatus::class)],
            'status' => ['nullable', Rule::enum(ActionItemStatus::class)],
            'response_hours' => ['nullable', 'integer', 'in:1,4,24,48'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
        ]);

        $photoPath = $request->hasFile('photo')
            ? $request->file('photo')->store("action-item-feedback/{$actionItem->id}", 'public')
            : null;

        $feedback = $actionItem->feedback()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'response_status' => ActionItemFeedbackStatus::from($validated['response_status']),
            'photo_path' => $photoPath,
            'photo_original_name' => $request->hasFile('photo') ? $request->file('photo')->getClientOriginalName() : null,
        ]);

        $status = isset($validated['status'])
            ? ActionItemStatus::from($validated['status'])
            : $actionItem->status;
        $responseHours = (int) ($validated['response_hours'] ?? $actionItem->response_hours ?? 24);

        $attributes = ['response_hours' => $responseHours];

        if (isset($validated['status'])) {
            $attributes['status'] = $status;
            $attributes['completed_at'] = $status === ActionItemStatus::Done ? ($actionItem->completed_at ?? now()) : null;
        }

        if ($status === ActionItemStatus::InProgress && $actionItem->response_due_at === null) {
            $attributes['response_due_at'] = now()->addHours($responseHours);
        }

        $actionItem->update($attributes);

        if ($isMine && ! $actionItem->first_responded_at) {
            $actionItem->update(['first_responded_at' => now()]);
        }

        $actionItem->loadMissing(['meeting', 'pic']);

        $recipientUsers = $recipients->resolve($actionItem->meeting, $request->user());

        if ($actionItem->pic && $actionItem->pic->id !== $request->user()->id) {
            $recipientUsers = $recipientUsers->push($actionItem->pic);
        }

        $recipientUsers
            ->unique('id')
            ->each(fn (User $user) => $user->notify(new ActionItemResponseNotification($actionItem, $feedback, $request->user())));

        $audit->log(
            'action_item.feedback_created',
            $actionItem,
            'Menambahkan respons pada action item',
            meetingId: $actionItem->meeting_id,
        );

        return back()->with('success', 'Respons berhasil ditambahkan.');
    }

    public function feedbackPhoto(ActionItem $actionItem, ActionItemFeedback $feedback): StreamedResponse
    {
        $this->authorize('view', $actionItem->meeting);

        abort_unless($feedback->action_item_id === $actionItem->id, 404);
        abort_unless($feedback->photo_path && Storage::disk('public')->exists($feedback->photo_path), 404);

        return Storage::disk('public')->response($feedback->photo_path);
    }
}
