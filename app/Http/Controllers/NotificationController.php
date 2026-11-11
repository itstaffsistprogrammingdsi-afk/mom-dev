<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20);

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'Semua notifikasi ditandai terbaca.');
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $row = $request->user()->notifications()->findOrFail($notification);
        $row->markAsRead();

        $url = $row->data['url'] ?? null;

        if (is_string($url) && $url !== '') {
            $path = parse_url($url, PHP_URL_PATH);
            $query = parse_url($url, PHP_URL_QUERY);

            if (is_string($path) && $path !== '') {
                return redirect()->to($path.($query ? '?'.$query : ''));
            }

            return redirect()->to($url);
        }

        return back()->with('success', 'Notifikasi ditandai terbaca.');
    }
}
