<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingSignature;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingSignatureController extends Controller
{
    public function store(Request $request, Meeting $meeting, string $role, AuditLogger $audit): RedirectResponse
    {
        $role = $this->validatedRole($role);
        $this->authorize($role === MeetingSignature::ROLE_CREATOR ? 'signCreator' : 'signApprover', $meeting);

        if ($meeting->isLocked()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'signature' => 'Tanda tangan tidak dapat diubah pada notulen yang terkunci.',
            ]);
        }

        $validated = $request->validate([
            'signature' => ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ]);

        $file = $validated['signature'];
        $path = $file->store("meeting-signatures/{$meeting->id}", 'local');
        $existing = $meeting->signatures()->where('role', $role)->first();

        $signature = $meeting->signatures()->updateOrCreate(
            ['role' => $role],
            [
                'signed_by' => $request->user()->id,
                'name' => $this->signatureName($meeting, $role),
                'signature_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'signed_at' => now(),
            ],
        );

        if ($existing && $existing->signature_path !== $path) {
            Storage::disk('local')->delete($existing->signature_path);
        }

        $audit->log(
            'meeting.signature_uploaded',
            $signature,
            'Mengunggah tanda tangan '.$role,
            ['role' => $role],
            $meeting->id,
        );

        return back()->with('success', 'Tanda tangan berhasil disimpan.');
    }

    public function destroy(Meeting $meeting, string $role, AuditLogger $audit): RedirectResponse
    {
        $role = $this->validatedRole($role);
        $this->authorize($role === MeetingSignature::ROLE_CREATOR ? 'signCreator' : 'signApprover', $meeting);

        if ($meeting->isLocked()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'signature' => 'Tanda tangan tidak dapat diubah pada notulen yang terkunci.',
            ]);
        }

        $signature = $meeting->signatures()->where('role', $role)->first();

        if (! $signature) {
            return back()->with('error', 'Tanda tangan tidak ditemukan.');
        }

        Storage::disk('local')->delete($signature->signature_path);
        $signature->delete();

        $audit->log(
            'meeting.signature_removed',
            null,
            'Menghapus tanda tangan '.$role,
            ['role' => $role],
            $meeting->id,
        );

        return back()->with('success', 'Tanda tangan dihapus.');
    }

    public function show(MeetingSignature $signature): StreamedResponse
    {
        $this->authorize('view', $signature->meeting);

        abort_unless(
            $signature->signature_path && Storage::disk('local')->exists($signature->signature_path),
            404,
        );

        return Storage::disk('local')->response(
            $signature->signature_path,
            $signature->original_name ?: 'tanda-tangan-'.$signature->role.'.png',
        );
    }

    private function validatedRole(string $role): string
    {
        abort_unless(in_array($role, MeetingSignature::ROLES, true), 404);

        return $role;
    }

    private function signatureName(Meeting $meeting, string $role): string
    {
        if ($role === MeetingSignature::ROLE_CREATOR) {
            return $meeting->creator?->name ?? 'Notulis';
        }

        return $meeting->approver?->name ?? (string) config('app.default_approver_name');
    }
}
