<?php

namespace App\Http\Controllers;

use App\Enums\AiSummaryStatus;
use App\Enums\Confidentiality;
use App\Jobs\GenerateMeetingSummary;
use App\Models\ActionItem;
use App\Models\AiSummary;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\ActionItemAssignedNotification;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MeetingAiController extends Controller
{
    public function generate(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'model_tier' => ['nullable', Rule::in(['auto', 'expert'])],
        ]);

        $tier = $validated['model_tier'] ?? 'auto';

        if ($meeting->confidentiality === Confidentiality::Secret && ! $request->boolean('ai_consent')) {
            throw ValidationException::withMessages([
                'ai_consent' => 'Notulen rahasia memerlukan persetujuan eksplisit sebelum diproses AI.',
            ]);
        }

        if (blank($meeting->content_text) && filled($meeting->content)) {
            $meeting->update(['content_text' => Meeting::contentToText($meeting->content)]);
        }

        if (blank($meeting->content_text)) {
            throw ValidationException::withMessages([
                'meeting' => 'Isi notulen masih kosong. Tulis pembahasan terlebih dahulu.',
            ]);
        }

        $this->failStaleSummaries($meeting);

        $pending = $meeting->summaries()
            ->whereIn('generation_status', ['queued', 'processing'])
            ->exists();

        if ($pending) {
            return back()->with('error', 'Resume AI masih diproses. Tunggu hingga selesai.');
        }

        $version = (int) $meeting->summaries()->max('version') + 1;

        $summary = $meeting->summaries()->create([
            'scope' => 'meeting',
            'content' => [],
            'version' => $version,
            'status' => AiSummaryStatus::Draft,
            'generation_status' => 'queued',
            'language' => 'id',
            'generated_by' => $request->user()->id,
        ]);

        GenerateMeetingSummary::dispatch($summary, $tier);

        return back()->with('success', 'Permintaan resume AI dibuat. Hasil akan muncul otomatis di panel Resume AI.');
    }

    public function status(Request $request, Meeting $meeting): JsonResponse
    {
        $this->authorize('view', $meeting);

        $this->failStaleSummaries($meeting);

        $summaries = $meeting->summaries()
            ->latest('version')
            ->limit(5)
            ->get()
            ->map(fn (AiSummary $summary) => [
                'id' => $summary->id,
                'version' => $summary->version,
                'status' => $summary->status->value,
                'generation_status' => $summary->generation_status,
                'error_message' => $summary->error_message,
                'model' => $summary->model,
                'approved_at' => $summary->approved_at?->toIso8601String(),
                'content' => $summary->content ?? [],
            ])
            ->values();

        return response()->json(['summaries' => $summaries]);
    }

    public function approve(Request $request, AiSummary $aiSummary, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $aiSummary->meeting);

        $aiSummary->update([
            'status' => AiSummaryStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $audit->log('ai_summary.approved', $aiSummary, 'Menyetujui resume AI', meetingId: $aiSummary->meeting_id);

        return back()->with('success', 'Resume AI disetujui.');
    }

    public function reject(Request $request, AiSummary $aiSummary, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $aiSummary->meeting);

        $aiSummary->update([
            'status' => AiSummaryStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $audit->log('ai_summary.rejected', $aiSummary, 'Menolak resume AI', meetingId: $aiSummary->meeting_id);

        return back()->with('success', 'Resume AI ditolak. Anda dapat membuat versi baru.');
    }

    public function update(Request $request, AiSummary $aiSummary, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $aiSummary->meeting);

        $validated = $request->validate([
            'content.ringkasan' => ['nullable', 'string'],
            'content.poin' => ['nullable', 'array'],
            'content.poin.*' => ['string'],
            'content.keputusan' => ['nullable', 'array'],
            'content.keputusan.*' => ['string'],
        ]);

        $content = $aiSummary->content ?? [];
        $content['ringkasan'] = $validated['content']['ringkasan'] ?? ($content['ringkasan'] ?? '');
        $content['poin'] = $validated['content']['poin'] ?? ($content['poin'] ?? []);
        $content['keputusan'] = $validated['content']['keputusan'] ?? ($content['keputusan'] ?? []);

        $aiSummary->update(['content' => $content]);

        $audit->log('ai_summary.updated', $aiSummary, 'Menyunting resume AI', meetingId: $aiSummary->meeting_id);

        return back()->with('success', 'Resume AI diperbarui.');
    }

    public function applyActionItems(Request $request, AiSummary $aiSummary, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $aiSummary->meeting);

        $suggestions = collect($aiSummary->content['action_items'] ?? [])
            ->filter(fn ($item) => is_array($item) && filled($item['title'] ?? null));

        if ($suggestions->isEmpty()) {
            return back()->with('error', 'Tidak ada usulan action item pada resume ini.');
        }

        $created = DB::transaction(function () use ($suggestions, $aiSummary) {
            $count = 0;

            foreach ($suggestions as $suggestion) {
                $title = trim((string) $suggestion['title']);

                if ($aiSummary->meeting->actionItems()->where('title', $title)->exists()) {
                    continue;
                }

                $pic = filled($suggestion['pic_name'] ?? null)
                    ? User::query()->where('name', $suggestion['pic_name'])->orWhere('email', $suggestion['pic_name'])->first()
                    : null;

                $division = filled($suggestion['division_name'] ?? null)
                    ? Division::query()->where('name', $suggestion['division_name'])->first()
                    : null;

                $dueDate = null;

                if (filled($suggestion['due_date'] ?? null)) {
                    try {
                        $dueDate = Carbon::parse($suggestion['due_date'])->toDateString();
                    } catch (\Throwable) {
                        $dueDate = null;
                    }
                }

                $item = ActionItem::create([
                    'meeting_id' => $aiSummary->meeting_id,
                    'title' => $title,
                    'description' => $suggestion['description'] ?? null,
                    'pic_id' => $pic?->id,
                    'pic_name' => $pic?->name ?? $suggestion['pic_name'] ?? null,
                    'division_id' => $division?->id,
                    'due_date' => $dueDate,
                    'status' => 'new',
                    'source' => 'ai',
                    'ai_confirmed' => true,
                ]);

                if ($pic && $pic->id !== $request->user()->id) {
                    $pic->notify(new ActionItemAssignedNotification($item));
                }

                $count++;
            }

            return $count;
        });

        $audit->log(
            'ai_summary.action_items_applied',
            $aiSummary,
            "Menerapkan {$created} usulan action item dari resume AI",
            ['created' => $created],
            $aiSummary->meeting_id,
        );

        return back()->with('success', "{$created} action item dari AI ditambahkan. Lengkapi PIC dan tenggat sebelum finalisasi.");
    }

    private function failStaleSummaries(Meeting $meeting): void
    {
        $meeting->summaries()
            ->whereIn('generation_status', ['queued', 'processing'])
            ->where('updated_at', '<', now()->subMinutes(10))
            ->update([
                'generation_status' => 'failed',
                'error_message' => 'Proses AI melewati batas waktu. Silakan jalankan ulang resume.',
                'completed_at' => now(),
            ]);
    }
}
