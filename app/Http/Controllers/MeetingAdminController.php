<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeetingAdminController extends Controller
{
    public function update(Request $request, Meeting $meeting, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('changeConfidentiality', $meeting);

        $validated = $request->validate([
            'admin_ids' => ['nullable', 'array'],
            'admin_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $adminIds = User::query()
            ->whereIn('id', $validated['admin_ids'] ?? [])
            ->where('role', 'admin')
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $meeting->admins()->sync($adminIds);

        $audit->log(
            'meeting.admins_updated',
            $meeting,
            'Memperbarui admin khusus dokumen',
            ['admin_ids' => $adminIds],
            $meeting->id,
        );

        return back()->with('success', 'Admin dokumen diperbarui.');
    }
}
