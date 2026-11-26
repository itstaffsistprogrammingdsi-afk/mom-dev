<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationPreferencesController extends Controller
{
    public function edit(Request $request): Response
    {
        $preferences = $request->user()->notification_preferences ?? [];

        return Inertia::render('settings/Notifications', [
            'preferences' => [
                'email' => (bool) ($preferences['email'] ?? true),
                'in_app' => (bool) ($preferences['in_app'] ?? true),
                'categories' => [
                    'meeting' => (bool) ($preferences['categories']['meeting'] ?? true),
                    'action_item' => (bool) ($preferences['categories']['action_item'] ?? true),
                    'quick_note' => (bool) ($preferences['categories']['quick_note'] ?? true),
                ],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'boolean'],
            'in_app' => ['required', 'boolean'],
            'categories' => ['required', 'array'],
            'categories.meeting' => ['required', 'boolean'],
            'categories.action_item' => ['required', 'boolean'],
            'categories.quick_note' => ['required', 'boolean'],
        ]);

        $request->user()->update(['notification_preferences' => $validated]);

        return back()->with('success', 'Preferensi notifikasi diperbarui.');
    }
}
