<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('division:id,name')
            ->when($request->string('search')->toString(), fn ($query, $search) => $query
                ->whereLike('name', "%{$search}%", caseSensitive: false)
                ->orWhereLike('email', "%{$search}%", caseSensitive: false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'divisions' => Division::query()->orderBy('name')->get(['id', 'name']),
            'roles' => collect(UserRole::cases())->map(fn (UserRole $role) => [
                'value' => $role->value,
                'label' => $role->label(),
            ]),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            ...$validated,
            'email_verified_at' => now(),
        ]);

        $audit->log('user.created', $user, 'Menambahkan pengguna');

        return back()->with('success', 'Pengguna ditambahkan.');
    }

    public function update(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $user->update($validated);

        $audit->log('user.updated', $user, 'Memperbarui data pengguna', $user->getChanges());

        return back()->with('success', 'Data pengguna diperbarui.');
    }
}
