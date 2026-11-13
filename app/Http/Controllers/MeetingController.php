<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\Confidentiality;
use App\Enums\DocumentType;
use App\Enums\MeetingType;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\MeetingTemplate;
use App\Models\MeetingVersion;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\MeetingFinalizedNotification;
use App\Services\AuditLogger;
use App\Services\CollabAvailability;
use App\Services\CollabTokenService;
use App\Services\MeetingContentParser;
use App\Services\MeetingTagSynchronizer;
use App\Services\MeetingNumberGenerator;
use App\Services\MeetingVersionService;
use App\Services\TipTapContentSanitizer;
use App\Services\Notifications\MeetingRecipientResolver;
use App\Services\TemplateContentFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $filters = $request->only(['search', 'division', 'topic', 'status', 'type', 'document_type', 'from', 'to']);

        if (filled($filters['search'] ?? null)) {
            $meetings = Meeting::search($filters['search'])
                ->query(fn (Builder $query) => $query
                    ->visibleTo($user)
                    ->filter(collect($filters)->except('search')->all())
                )
                ->paginate(12)
                ->withQueryString();
        } else {
            $meetings = Meeting::query()
                ->visibleTo($user)
                ->filter($filters)
                ->select([
                    'id', 'title', 'document_type', 'meeting_type', 'division_id', 'created_by',
                    'scheduled_at', 'status', 'confidentiality',
                ])
                ->with(['division:id,name', 'creator:id,name'])
                ->orderByDesc('scheduled_at')
                ->paginate(12)
                ->withQueryString();
        }

        $meetings->load(['division:id,name', 'creator:id,name']);

        return Inertia::render('Meetings/Index', [
            'meetings' => $meetings,
            'filters' => $filters,
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'topics' => Topic::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'documentTypes' => collect(DocumentType::cases())->map(fn (DocumentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'canCreate' => $user->canWriteMinutes(),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Meeting::class);

        $contentFactory = app(TemplateContentFactory::class);

        $templates = MeetingTemplate::query()
            ->where('is_active', true)
            ->orderBy('document_type')
            ->orderBy('name')
            ->get()
            ->each(function (MeetingTemplate $template) use ($contentFactory) {
                $template->content ??= $contentFactory->default($template->document_type);
            });

        return Inertia::render('Meetings/Create', [
            'templates' => $templates,
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'division_id']),
            'topics' => Topic::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'defaultContent' => $contentFactory->default(DocumentType::Meeting),
            'documentTypes' => collect(DocumentType::cases())->map(fn (DocumentType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ]),
            'creatorName' => $request->user()->name,
            'approverName' => config('app.default_approver_name'),
        ]);
    }

    public function store(
        Request $request,
        MeetingContentParser $parser,
        MeetingTagSynchronizer $tagger,
        AuditLogger $audit,
        TipTapContentSanitizer $sanitizer,
    ): RedirectResponse {
        $this->authorize('create', Meeting::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'nomor_notulensi' => ['nullable', 'string', 'max:255'],
            'editor_mode' => ['nullable', Rule::in(['docs', 'sheet'])],
            'meeting_type' => ['required', Rule::enum(MeetingType::class)],
            'meeting_template_id' => ['nullable', 'exists:meeting_templates,id'],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'scheduled_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'agenda' => ['nullable', 'string'],
            'confidentiality' => ['required', Rule::enum(Confidentiality::class)],
            'participant_ids' => ['nullable', 'array'],
            'participant_ids.*' => ['integer', 'exists:users,id'],
            'content' => ['nullable', 'array'],
        ]);

        $template = filled($validated['meeting_template_id'] ?? null)
            ? MeetingTemplate::query()->find($validated['meeting_template_id'])
            : null;

        $documentType = $template?->document_type ?? DocumentType::Meeting;

        $content = $sanitizer->sanitize(
            $validated['content']
            ?? $template?->content
            ?? app(TemplateContentFactory::class)->default($documentType)
        );

        $division = Division::query()->find($validated['division_id'] ?? null);
        $nomor = trim((string) ($validated['nomor_notulensi'] ?? ''))
            ?: app(MeetingNumberGenerator::class)->generate($division, $validated['scheduled_at'] ?? null);

        $meeting = Meeting::create([
            ...collect($validated)->except(['participant_ids', 'content'])->all(),
            'nomor_notulensi' => $nomor,
            'editor_mode' => $validated['editor_mode'] ?? 'sheet',
            'document_type' => $documentType->value,
            'created_by' => $request->user()->id,
            'content' => $content,
            'content_text' => $parser->parse($content)['text'],
            'content_updated_at' => now(),
        ]);

        $participants = User::query()->whereIn('id', $validated['participant_ids'] ?? [])->get();

        foreach ($participants as $participant) {
            $meeting->participants()->create([
                'user_id' => $participant->id,
                'name' => $participant->name,
                'email' => $participant->email,
                'attendance' => AttendanceStatus::Invited,
            ]);
        }

        $tagger->sync($meeting, $content, $request->user()->id);
        $audit->log('meeting.created', $meeting, 'Membuat notulen baru', meetingId: $meeting->id);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Notulen berhasil dibuat.');
    }

    public function show(Request $request, Meeting $meeting): Response
    {
        $this->authorize('view', $meeting);

        $meeting->load([
            'division:id,name',
            'creator:id,name',
            'approvedBy:id,name',
            'template:id,name,document_type',
            'participants.user:id,name,email',
            'tags',
            'decisions.topic:id,name,slug',
            'actionItems.pic:id,name',
            'actionItems.division:id,name',
            'actionItems.feedback.user:id,name',
            'summaries' => fn ($query) => $query->latest('version')->limit(5),
            'attachments',
            'versions' => fn ($query) => $query->latest('version')->limit(10),
        ]);

        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'can' => [
                'update' => $request->user()->can('update', $meeting),
                'finalize' => $request->user()->can('finalize', $meeting),
                'generateAi' => $request->user()->can('update', $meeting),
                'manage' => $request->user()->can('update', $meeting),
                'share' => $request->user()->can('share', $meeting),
            ],
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function edit(Request $request, Meeting $meeting): Response
    {
        $this->authorize('update', $meeting);

        $meeting->load(['division:id,name,head_id', 'division.head:id,name', 'approvedBy:id,name', 'participants.user:id,name', 'tags']);

        $tokens = app(CollabTokenService::class);
        $collabAvailable = app(CollabAvailability::class)->isAvailable();

        return Inertia::render('Meetings/Edit', [
            'meeting' => $meeting,
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'topics' => Topic::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'collab' => $collabAvailable ? [
                'url' => config('collab.url'),
                'document' => "meeting-{$meeting->id}",
                'token' => $tokens->issue($meeting, $request->user(), true),
                'user' => $request->user()->name,
            ] : null,
            'creatorName' => $meeting->creator?->name ?? $request->user()->name,
            'approverName' => $meeting->approvedBy?->name ?? config('app.default_approver_name'),
        ]);
    }

    public function autosave(
        Request $request,
        Meeting $meeting,
        MeetingContentParser $parser,
        MeetingTagSynchronizer $tagger,
        TipTapContentSanitizer $sanitizer,
    ): JsonResponse {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'nomor_notulensi' => ['sometimes', 'nullable', 'string', 'max:255'],
            'editor_mode' => ['sometimes', Rule::in(['docs', 'sheet'])],
            'agenda' => ['nullable', 'string'],
            'confidentiality' => ['sometimes', Rule::enum(Confidentiality::class)],
            'content' => ['nullable', 'array'],
        ]);

        $content = $sanitizer->sanitize($validated['content'] ?? $meeting->content);

        $nomor = trim((string) ($validated['nomor_notulensi'] ?? $meeting->nomor_notulensi));

        if ($nomor === '') {
            $nomor = app(MeetingNumberGenerator::class)->generate($meeting->division, $meeting->scheduled_at);
        }

        $meeting->update([
            'title' => $validated['title'] ?? $meeting->title,
            'nomor_notulensi' => $nomor,
            'editor_mode' => $validated['editor_mode'] ?? $meeting->editor_mode ?? 'docs',
            'agenda' => $validated['agenda'] ?? $meeting->agenda,
            'confidentiality' => $validated['confidentiality'] ?? $meeting->confidentiality,
            'content' => $content,
            'content_text' => $parser->parse($content)['text'],
            'content_updated_at' => now(),
        ]);

        $tagger->sync($meeting, $content, $request->user()->id);

        return response()->json([
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function update(
        Request $request,
        Meeting $meeting,
        MeetingContentParser $parser,
        MeetingTagSynchronizer $tagger,
        AuditLogger $audit,
        TipTapContentSanitizer $sanitizer,
    ): RedirectResponse {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'nomor_notulensi' => ['nullable', 'string', 'max:255'],
            'editor_mode' => ['nullable', Rule::in(['docs', 'sheet'])],
            'agenda' => ['nullable', 'string'],
            'confidentiality' => ['required', Rule::enum(Confidentiality::class)],
            'content' => ['nullable', 'array'],
        ]);

        $content = $sanitizer->sanitize($validated['content'] ?? $meeting->content);

        $nomor = trim((string) ($validated['nomor_notulensi'] ?? ''))
            ?: $meeting->nomor_notulensi
            ?: app(MeetingNumberGenerator::class)->generate($meeting->division, $meeting->scheduled_at);

        $meeting->update([
            ...$validated,
            'nomor_notulensi' => $nomor,
            'editor_mode' => $validated['editor_mode'] ?? $meeting->editor_mode ?? 'docs',
            'content' => $content,
            'content_text' => $parser->parse($content)['text'],
            'content_updated_at' => now(),
        ]);

        $tagger->sync($meeting, $content, $request->user()->id);

        if ($meeting->isFinal()) {
            app(MeetingVersionService::class)->snapshot($meeting, 'admin_edit', $request->user()->id);
        }

        $audit->log('meeting.updated', $meeting, 'Memperbarui isi notulen', meetingId: $meeting->id);

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Perubahan notulen disimpan.');
    }

    public function finalize(
        Request $request,
        Meeting $meeting,
        AuditLogger $audit,
        MeetingVersionService $versions,
        MeetingRecipientResolver $recipients,
    ): RedirectResponse {
        $this->authorize('finalize', $meeting);

        if ($meeting->isFinal() || $meeting->isLocked()) {
            throw ValidationException::withMessages([
                'meeting' => 'Notulen ini sudah difinalkan atau dikunci.',
            ]);
        }

        $meeting->loadMissing('actionItems');

        $incomplete = $meeting->actionItems->filter(
            fn ($item) => (blank($item->pic_id) && blank($item->pic_name)) || blank($item->due_date)
        );

        if ($incomplete->isNotEmpty()) {
            throw ValidationException::withMessages([
                'meeting' => 'Lengkapi PIC dan tenggat untuk '.$incomplete->count().' action item sebelum finalisasi.',
            ]);
        }

        DB::transaction(function () use ($request, $meeting, $audit, $versions) {
            $meeting->update([
                'status' => 'final',
                'finalized_at' => now(),
                'approved_by' => $request->user()->id,
            ]);

            $versions->snapshot($meeting, 'finalized', $request->user()->id);

            $audit->log('meeting.finalized', $meeting, 'Memfinalkan notulen', meetingId: $meeting->id);
        });

        $meeting->refresh()->loadMissing('actionItems');

        $recipients->resolve($meeting, $request->user())->each(function (User $user) use ($meeting) {
            $tasks = $meeting->actionItems
                ->where('pic_id', $user->id)
                ->map(fn ($item) => [
                    'title' => $item->title,
                    'due_date' => $item->due_date?->format('d M Y'),
                ])
                ->values()
                ->all();

            $user->notify(new MeetingFinalizedNotification($meeting, $tasks));
        });

        return redirect()->route('meetings.show', $meeting)
            ->with('success', 'Notulen difinalkan, versi dikunci, dan notifikasi dikirim.');
    }

    public function lock(Request $request, Meeting $meeting, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('lock', $meeting);

        if (! $meeting->isFinal()) {
            throw ValidationException::withMessages(['meeting' => 'Hanya notulen final yang dapat dikunci.']);
        }

        $meeting->update(['status' => 'locked']);
        $audit->log('meeting.locked', $meeting, 'Mengunci notulen', meetingId: $meeting->id);

        return back()->with('success', 'Notulen dikunci.');
    }

    public function unlock(Request $request, Meeting $meeting, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('unlock', $meeting);

        if (! $meeting->isLocked()) {
            throw ValidationException::withMessages(['meeting' => 'Notulen tidak dalam status terkunci.']);
        }

        $meeting->update(['status' => 'final']);
        $audit->log('meeting.unlocked', $meeting, 'Membuka kunci notulen', meetingId: $meeting->id);

        return back()->with('success', 'Kunci notulen dibuka.');
    }

    public function restoreVersion(
        Request $request,
        Meeting $meeting,
        MeetingVersion $version,
        MeetingTagSynchronizer $tagger,
        AuditLogger $audit,
    ): RedirectResponse {
        $this->authorize('update', $meeting);

        abort_unless($version->meeting_id === $meeting->id, 404);

        $meeting->update([
            'content' => $version->content,
            'content_text' => $version->content_text,
            'content_updated_at' => now(),
        ]);

        $tagger->sync($meeting, $version->content, $request->user()->id);
        app(MeetingVersionService::class)->snapshot($meeting, 'restore_v'.$version->version, $request->user()->id);

        $audit->log('meeting.version_restored', $meeting, "Memulihkan versi {$version->version}", meetingId: $meeting->id);

        return back()->with('success', "Versi {$version->version} dipulihkan.");
    }
}
