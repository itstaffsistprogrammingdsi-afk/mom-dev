<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/Topics/Index', [
            'topics' => Topic::query()
                ->withCount('meetings')
                ->orderBy('name')
                ->get(),
            'filter' => $request->only('status'),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:topics,name'],
            'description' => ['nullable', 'string'],
        ]);

        $topic = Topic::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $audit->log('topic.created', $topic, 'Menambahkan topik', $validated);

        return back()->with('success', 'Topik ditambahkan.');
    }

    public function toggleStatus(Topic $topic, AuditLogger $audit): RedirectResponse
    {
        $topic->update([
            'status' => $topic->status === 'active' ? 'archived' : 'active',
        ]);

        $audit->log('topic.status_updated', $topic, 'Mengubah status topik', [
            'status' => $topic->status,
        ]);

        return back()->with('success', 'Status topik diperbarui.');
    }
}
