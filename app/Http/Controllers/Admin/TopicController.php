<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:topics,name'],
            'description' => ['nullable', 'string'],
        ]);

        Topic::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Topik ditambahkan.');
    }

    public function toggleStatus(Topic $topic): RedirectResponse
    {
        $topic->update([
            'status' => $topic->status === 'active' ? 'archived' : 'active',
        ]);

        return back()->with('success', 'Status topik diperbarui.');
    }
}
