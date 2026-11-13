<?php

namespace App\Http\Controllers;

use App\Enums\ActionItemFeedbackStatus;
use App\Models\Division;
use App\Models\QuickNote;
use App\Models\QuickNoteMention;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\QuickNoteMentionNotification;
use App\Notifications\QuickNoteResponseNotification;
use App\Services\MeetingContentParser;
use App\Services\QuickNoteMentionSynchronizer;
use App\Services\TipTapContentSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class QuickNoteController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $scope = in_array($request->string('scope')->toString(), ['mine', 'mentions'], true)
            ? $request->string('scope')->toString()
            : 'all';
        $search = trim($request->string('search')->toString());
        $division = $request->string('division')->toString();

        $query = QuickNote::query()
            ->visibleTo($user)
            ->with(['creator:id,name', 'division:id,name', 'mentions.user:id,name'])
            ->latest();

        if ($scope === 'mine') {
            $query->where('user_id', $user->id);
        }

        if ($scope === 'mentions') {
            $query->whereHas('mentions', fn ($mention) => $mention->where('user_id', $user->id));
        }

        if ($division !== '') {
            $query->where('division_id', $division);
        }

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->whereLike('title', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('content_text', "%{$search}%", caseSensitive: false);
            });
        }

        $mentionBase = QuickNoteMention::query()
            ->where('user_id', $user->id)
            ->whereHas('quickNote', fn ($query) => $query->visibleTo($user));

        return Inertia::render('QuickNotes/Index', [
            'notes' => $query->paginate(15)->withQueryString(),
            'filters' => [
                'scope' => $scope,
                'search' => $search,
                'division' => $division,
            ],
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'visible' => QuickNote::query()->visibleTo($user)->count(),
                'mine' => QuickNote::query()->where('user_id', $user->id)->count(),
                'pending' => (clone $mentionBase)->whereNull('responded_at')->count(),
                'overdue' => (clone $mentionBase)->whereNull('responded_at')->where('due_at', '<', now())->count(),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', QuickNote::class);

        return Inertia::render('QuickNotes/Create', $this->formProps());
    }

    public function store(
        Request $request,
        MeetingContentParser $parser,
        QuickNoteMentionSynchronizer $synchronizer,
        TipTapContentSanitizer $sanitizer,
    ): RedirectResponse {
        $this->authorize('create', QuickNote::class);

        $validated = $this->validatePayload($request);
        $content = $sanitizer->sanitize($validated['content'] ?? null);
        $contentText = $parser->parse($content)['text'];
        $photoFiles = array_slice($request->file('photos', []), 0, $this->blobImageCount($content));

        [$quickNote, $createdMentions] = DB::transaction(function () use ($request, $validated, $content, $contentText, $synchronizer, $parser, $photoFiles) {
            $newPhotos = $this->storePhotoFiles($photoFiles);
            $cover = $newPhotos === [] ? null : reset($newPhotos);

            $quickNote = QuickNote::create([
                'user_id' => $request->user()->id,
                'division_id' => $request->user()->division_id,
                'title' => $this->resolveTitle($validated['title'] ?? null, $contentText),
                'content' => $content,
                'content_text' => $contentText,
                'photo_path' => $cover['path'] ?? null,
                'photo_original_name' => $cover['original_name'] ?? null,
                'photos' => $newPhotos === [] ? null : $newPhotos,
            ]);

            if ($newPhotos !== []) {
                $content = $this->replaceBlobImageSources($content, $this->photoReplacements($quickNote, $newPhotos));
                $quickNote->update([
                    'content' => $content,
                    'content_text' => $parser->parse($content)['text'],
                ]);
            }

            $createdMentions = $synchronizer->sync($quickNote, $content, $this->responseHours($validated['mentions'] ?? []));

            return [$quickNote, $createdMentions];
        });

        $this->notifyMentions($quickNote, $createdMentions);

        return redirect()
            ->route('quick-notes.show', $quickNote)
            ->with('success', 'Quick Note berhasil dibuat.');
    }

    public function show(Request $request, QuickNote $quickNote): Response
    {
        $this->authorize('view', $quickNote);

        $quickNote->load(['creator:id,name', 'mentions.user:id,name']);

        return Inertia::render('QuickNotes/Show', [
            'note' => $quickNote,
            'can' => [
                'update' => $request->user()->can('update', $quickNote),
                'delete' => $request->user()->can('delete', $quickNote),
            ],
            'currentUserId' => $request->user()->id,
        ]);
    }

    public function edit(Request $request, QuickNote $quickNote): Response
    {
        $this->authorize('update', $quickNote);

        $quickNote->load(['mentions.user:id,name']);

        return Inertia::render('QuickNotes/Edit', [
            ...$this->formProps(),
            'note' => $quickNote,
        ]);
    }

    public function update(
        Request $request,
        QuickNote $quickNote,
        MeetingContentParser $parser,
        QuickNoteMentionSynchronizer $synchronizer,
        TipTapContentSanitizer $sanitizer,
    ): RedirectResponse {
        $this->authorize('update', $quickNote);

        $validated = $this->validatePayload($request);
        $content = $sanitizer->sanitize($validated['content'] ?? $quickNote->content);
        $existingPhotos = $quickNote->photos ?? [];
        $content = $this->replaceLegacyCoverImages($quickNote, $content, $existingPhotos);

        $photoFiles = array_slice($request->file('photos', []), 0, $this->blobImageCount($content));
        $newPhotos = $this->storePhotoFiles($photoFiles);
        $content = $this->replaceBlobImageSources($content, $this->photoReplacements($quickNote, $newPhotos));

        $allPhotos = [...$existingPhotos, ...$newPhotos];
        $referencedIds = $this->referencedPhotoIds($content);
        $keptPhotos = array_intersect_key($allPhotos, array_flip($referencedIds));
        $removedPhotos = array_diff_key($allPhotos, $keptPhotos);
        $cover = $keptPhotos === [] ? null : reset($keptPhotos);

        $oldCoverPath = $quickNote->photo_path;
        $newCoverPath = $cover['path'] ?? null;
        $contentText = $parser->parse($content)['text'];

        $photoAttributes = [
            'photos' => $keptPhotos === [] ? null : $keptPhotos,
            'photo_path' => $newCoverPath,
            'photo_original_name' => $cover['original_name'] ?? null,
        ];

        $createdMentions = DB::transaction(function () use ($quickNote, $validated, $content, $contentText, $synchronizer, $photoAttributes) {
            $quickNote->update([
                'title' => $this->resolveTitle($validated['title'] ?? null, $contentText, $quickNote->title),
                'content' => $content,
                'content_text' => $contentText,
                ...$photoAttributes,
            ]);

            return $synchronizer->sync($quickNote, $content, $this->responseHours($validated['mentions'] ?? []));
        });

        $keptPaths = collect($keptPhotos)->pluck('path')->filter();
        $removedPaths = collect($removedPhotos)->pluck('path')->filter();

        if ($oldCoverPath && $oldCoverPath !== $newCoverPath && ! $keptPaths->contains($oldCoverPath)) {
            $removedPaths->push($oldCoverPath);
        }

        if ($removedPaths->isNotEmpty()) {
            Storage::disk('public')->delete($removedPaths->unique()->values()->all());
        }

        $this->notifyMentions($quickNote, $createdMentions);

        return redirect()
            ->route('quick-notes.show', $quickNote)
            ->with('success', 'Quick Note diperbarui.');
    }

    public function destroy(Request $request, QuickNote $quickNote): RedirectResponse
    {
        $this->authorize('delete', $quickNote);

        $quickNote->delete();

        return redirect()
            ->route('quick-notes.index')
            ->with('success', 'Quick Note dihapus.');
    }

    public function photo(Request $request, QuickNote $quickNote): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $quickNote);

        abort_unless($quickNote->photo_path && Storage::disk('public')->exists($quickNote->photo_path), 404);

        return Storage::disk('public')->response($quickNote->photo_path);
    }

    public function photoItem(Request $request, QuickNote $quickNote, string $photo): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorize('view', $quickNote);

        $path = $quickNote->photos[$photo]['path'] ?? null;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function respond(
        Request $request,
        QuickNote $quickNote,
    ): RedirectResponse {
        $this->authorize('respond', $quickNote);

        $validated = $request->validate([
            'response_status' => ['required', Rule::enum(ActionItemFeedbackStatus::class)],
            'response_body' => ['nullable', 'string', 'max:1000'],
        ]);

        $mention = $quickNote->mentions()
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $mention->update([
            'response_status' => ActionItemFeedbackStatus::from($validated['response_status']),
            'response_body' => $validated['response_body'] ?? null,
            'responded_at' => $mention->responded_at ?? now(),
        ]);

        $quickNote->loadMissing('creator');

        if ($quickNote->creator && $quickNote->creator->id !== $request->user()->id) {
            $quickNote->creator->notify(new QuickNoteResponseNotification($quickNote, $mention, $request->user()));
        }

        return back()->with('success', 'Respons berhasil dikirim.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'array'],
            'mentions' => ['nullable', 'array'],
            'mentions.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'mentions.*.response_hours' => ['required', 'integer', 'in:1,4,24,48'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
        ]);
    }

    /**
     * @param  array<int, array{user_id: int|string, response_hours: int|string}>  $mentions
     * @return array<int, int>
     */
    private function responseHours(array $mentions): array
    {
        return collect($mentions)
            ->mapWithKeys(fn (array $mention) => [(int) $mention['user_id'] => (int) $mention['response_hours']])
            ->all();
    }

    private function resolveTitle(?string $title, string $contentText, ?string $fallback = null): string
    {
        $title = trim((string) $title);

        if ($title !== '') {
            return $title;
        }

        if ($contentText !== '') {
            return Str::limit($contentText, 80);
        }

        return $fallback ?? 'Catatan cepat';
    }

    /**
     * @param  array<int, QuickNoteMention>  $mentions
     */
    private function notifyMentions(QuickNote $quickNote, array $mentions): void
    {
        if ($mentions === []) {
            return;
        }

        $quickNote->loadMissing('creator');

        foreach ($mentions as $mention) {
            if ($mention->user_id === $quickNote->user_id) {
                continue;
            }

            $mention->loadMissing('user');
            $mention->user?->notify(new QuickNoteMentionNotification($quickNote));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(): array
    {
        return [
            'users' => User::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'division_id']),
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'topics' => Topic::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'responseOptions' => collect([1, 4, 24, 48])
                ->map(fn (int $hours) => [
                    'value' => $hours,
                    'label' => "{$hours} jam",
                ])
                ->values(),
            'emptyContent' => $this->emptyContent(),
        ];
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @return array<string, array{path: string, original_name: string}>
     */
    private function storePhotoFiles(array $files): array
    {
        $photos = [];

        foreach ($files as $file) {
            if (! $file) {
                continue;
            }

            $photos[(string) Str::uuid()] = [
                'path' => $file->store('quick-notes', 'public'),
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        return $photos;
    }

    /**
     * @param  array<string, array{path: string, original_name: string}>  $photos
     * @return array<int, array{url: string, photoId: string}>
     */
    private function photoReplacements(QuickNote $quickNote, array $photos): array
    {
        return collect($photos)
            ->map(fn (array $photo, string $id) => [
                'url' => route('quick-notes.photo-item', [$quickNote, $id]),
                'photoId' => $id,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<int, array{url: string, photoId: string}>  $replacements
     * @return array<string, mixed>
     */
    private function replaceBlobImageSources(array $content, array $replacements): array
    {
        $queue = $replacements;

        $walk = function (array $node) use (&$walk, &$queue): array {
            if (($node['type'] ?? null) === 'image') {
                $src = $node['attrs']['src'] ?? null;

                if (! is_string($src) || str_starts_with($src, 'blob:') || $src === '') {
                    $next = array_shift($queue);

                    if ($next) {
                        $node['attrs'] = [
                            ...($node['attrs'] ?? []),
                            'src' => $next['url'],
                            'photoId' => $next['photoId'],
                        ];
                    }
                }
            }

            foreach ($node['content'] ?? [] as $index => $child) {
                if (is_array($child)) {
                    $node['content'][$index] = $walk($child);
                }
            }

            return $node;
        };

        return $walk($content);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private function blobImageCount(array $content): int
    {
        $count = 0;

        $walk = function (array $node) use (&$walk, &$count): void {
            if (($node['type'] ?? null) === 'image') {
                $src = $node['attrs']['src'] ?? null;

                if (! is_string($src) || str_starts_with($src, 'blob:') || $src === '') {
                    $count++;
                }
            }

            foreach ($node['content'] ?? [] as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($content);

        return $count;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<int, string>
     */
    private function referencedPhotoIds(array $content): array
    {
        $ids = [];

        $walk = function (array $node) use (&$walk, &$ids): void {
            if (($node['type'] ?? null) === 'image') {
                $photoId = $node['attrs']['photoId'] ?? null;

                if (is_string($photoId) && $photoId !== '') {
                    $ids[] = $photoId;
                }
            }

            foreach ($node['content'] ?? [] as $child) {
                if (is_array($child)) {
                    $walk($child);
                }
            }
        };

        $walk($content);

        return array_values(array_unique($ids));
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<string, array{path: string, original_name: string}>  $photos
     * @return array<string, mixed>
     */
    private function replaceLegacyCoverImages(QuickNote $quickNote, array $content, array &$photos): array
    {
        if (! $quickNote->photo_path) {
            return $content;
        }

        $coverUrl = route('quick-notes.photo', $quickNote);
        $uuid = null;

        $walk = function (array $node) use (&$walk, &$uuid, $coverUrl, $quickNote, &$photos): array {
            if (($node['type'] ?? null) === 'image') {
                $src = $node['attrs']['src'] ?? null;
                $photoId = $node['attrs']['photoId'] ?? null;

                if ($src === $coverUrl && ! $photoId) {
                    $uuid ??= (string) Str::uuid();
                    $photos[$uuid] = [
                        'path' => $quickNote->photo_path,
                        'original_name' => $quickNote->photo_original_name ?? 'foto.jpg',
                    ];

                    $node['attrs'] = [
                        ...($node['attrs'] ?? []),
                        'src' => route('quick-notes.photo-item', [$quickNote, $uuid]),
                        'photoId' => $uuid,
                    ];
                }
            }

            foreach ($node['content'] ?? [] as $index => $child) {
                if (is_array($child)) {
                    $node['content'][$index] = $walk($child);
                }
            }

            return $node;
        };

        return $walk($content);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyContent(): array
    {
        return [
            'type' => 'doc',
            'content' => [
                ['type' => 'paragraph'],
            ],
        ];
    }
}
