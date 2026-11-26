<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->loadMissing('division:id,name');

        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->value ?? 'member',
                    'role_label' => $user->role?->label() ?? 'Anggota',
                    'job_title' => $user->job_title,
                    'division_id' => $user->division_id,
                    'division' => $user->division?->only(['id', 'name']),
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ] : null,
            ],
            'notifications' => [
                'unread' => fn () => $user
                    ? Cache::remember("notifications:unread:{$user->id}", now()->addSeconds(15), fn () => $user->unreadNotifications()->count())
                    : 0,
            ],
            'preferences' => [
                'locale' => $user?->locale ?? app()->getLocale(),
                'appearance' => $user?->appearance ?? 'system',
                'timezone' => $user?->timezone ?? 'Asia/Jakarta',
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
