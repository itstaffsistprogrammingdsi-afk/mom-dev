<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Divisions/Index', [
            'divisions' => Division::query()
                ->withCount(['users', 'meetings'])
                ->with('head:id,name')
                ->orderBy('name')
                ->get(),
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:divisions,name'],
            'description' => ['nullable', 'string'],
            'head_id' => ['nullable', 'exists:users,id'],
        ]);

        Division::create($validated);

        return back()->with('success', 'Divisi ditambahkan.');
    }
}
