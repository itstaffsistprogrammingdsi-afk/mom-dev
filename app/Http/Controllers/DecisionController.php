<?php

namespace App\Http\Controllers;

use App\Models\Decision;
use App\Models\Meeting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DecisionController extends Controller
{
    public function store(Request $request, Meeting $meeting, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $decision = $meeting->decisions()->create($this->validated($request));

        $audit->log('decision.created', $decision, 'Menambahkan keputusan', meetingId: $meeting->id);

        return back()->with('success', 'Keputusan ditambahkan.');
    }

    public function update(Request $request, Decision $decision, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $decision->meeting);

        $decision->update($this->validated($request));

        $audit->log('decision.updated', $decision, 'Memperbarui keputusan', meetingId: $decision->meeting_id);

        return back()->with('success', 'Keputusan diperbarui.');
    }

    public function destroy(Decision $decision, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $decision->meeting);

        $meetingId = $decision->meeting_id;
        $decision->delete();

        $audit->log('decision.deleted', null, 'Menghapus keputusan', meetingId: $meetingId);

        return back()->with('success', 'Keputusan dihapus.');
    }

    /**
     * @return array{content: string, topic_id: int|null, decided_at: string|null}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'decided_at' => ['nullable', 'date'],
        ]);

        return [
            'content' => $validated['content'],
            'topic_id' => $validated['topic_id'] ?? null,
            'decided_at' => $validated['decided_at'] ?? null,
        ];
    }
}
