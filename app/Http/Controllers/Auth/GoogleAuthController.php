<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        abort_if(blank($clientId) || blank($clientSecret), 404);

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
            'prompt' => 'select_account',
            'state' => $state,
        ]);

        return redirect()->away('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    public function callback(Request $request): RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        abort_if(blank($clientId) || blank($clientSecret), 404);

        $expectedState = $request->session()->pull('google_oauth_state');
        $state = $request->string('state')->toString();

        if (! $expectedState || ! hash_equals($expectedState, $state)) {
            return redirect()->route('login')->with('error', 'Sesi login Google tidak valid. Silakan coba lagi.');
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            return redirect()->route('login')->with('error', 'Login Google dibatalkan.');
        }

        $tokenResponse = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if ($tokenResponse->failed()) {
            return redirect()->route('login')->with('error', 'Google menolak proses login. Silakan coba lagi.');
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            return redirect()->route('login')->with('error', 'Token Google tidak valid.');
        }

        $profileResponse = Http::withToken($accessToken)->timeout(15)->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if ($profileResponse->failed()) {
            return redirect()->route('login')->with('error', 'Profil Google tidak dapat dibaca.');
        }

        $profile = $profileResponse->json();
        $email = strtolower((string) ($profile['email'] ?? ''));

        if ($email === '') {
            return redirect()->route('login')->with('error', 'Akun Google tidak memiliki email.');
        }

        $user = User::query()
            ->where('google_id', $profile['id'] ?? null)
            ->orWhere('email', $email)
            ->first();

        if ($user && ! $user->is_active) {
            return redirect()->route('login')->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        if (! $user) {
            $user = User::create([
                'name' => $profile['name'] ?? $email,
                'email' => $email,
                'password' => Str::random(40),
                'role' => 'member',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        }

        $user->forceFill([
            'google_id' => $profile['id'] ?? $user->google_id,
            'avatar' => $profile['picture'] ?? $user->avatar,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'last_login_at' => now(),
        ])->save();

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function redirectUri(): string
    {
        return (string) (config('services.google.redirect') ?: route('auth.google.callback'));
    }
}
