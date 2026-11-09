<?php

namespace App\Http\Controllers;

use App\Enums\AttachmentKind;
use App\Models\Attachment;
use App\Models\Meeting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function store(Request $request, Meeting $meeting, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'file' => ['nullable', 'file', 'max:20480'],
            'url' => ['nullable', 'url', 'max:2048'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $request->hasFile('file') && blank($validated['url'] ?? null)) {
            return back()->with('error', 'Pilih file atau masukkan tautan lampiran.');
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store("attachments/{$meeting->id}", 'local');

            $attachment = $meeting->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'kind' => AttachmentKind::File,
                'disk' => 'local',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        } else {
            $attachment = $meeting->attachments()->create([
                'uploaded_by' => $request->user()->id,
                'kind' => AttachmentKind::Link,
                'disk' => 'local',
                'url' => $validated['url'],
                'original_name' => $validated['name'] ?? $validated['url'],
                'size' => 0,
            ]);
        }

        $audit->log('attachment.created', $attachment, 'Menambahkan lampiran', meetingId: $meeting->id);

        return back()->with('success', 'Lampiran ditambahkan.');
    }

    public function download(Attachment $attachment): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $attachment->meeting);

        if ($attachment->kind === AttachmentKind::Link) {
            return redirect()->away($attachment->url);
        }

        abort_unless($attachment->path && Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Attachment $attachment, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('update', $attachment->meeting);

        if ($attachment->kind !== AttachmentKind::Link && $attachment->path) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        }

        $meetingId = $attachment->meeting_id;
        $attachment->delete();

        $audit->log('attachment.deleted', null, 'Menghapus lampiran', meetingId: $meetingId);

        return back()->with('success', 'Lampiran dihapus.');
    }
}
