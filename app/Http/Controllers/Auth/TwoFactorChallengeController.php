<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! $request->session()->has('two_factor_user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('auth/TwoFactorChallenge');
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20'],
        ]);

        $user = User::query()->find($request->session()->get('two_factor_user_id'));

        if (! $user || ! $user->is_active || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['two_factor_user_id', 'two_factor_remember']);

            return redirect()->route('login')->with('error', 'Sesi autentikasi dua faktor tidak valid.');
        }

        $code = trim($validated['code']);
        $google2fa = app(Google2FA::class);

        if ($google2fa->verifyKey($user->two_factor_secret, $code)) {
            return $this->login($request, $user, $audit);
        }

        $recoveryCodes = $user->two_factor_recovery_codes ?? [];
        $recoveryIndex = array_search($code, $recoveryCodes, true);

        if ($recoveryIndex !== false) {
            unset($recoveryCodes[$recoveryIndex]);
            $user->forceFill(['two_factor_recovery_codes' => array_values($recoveryCodes)])->save();

            return $this->login($request, $user, $audit);
        }

        throw ValidationException::withMessages([
            'code' => 'Kode autentikasi tidak valid.',
        ]);
    }

    private function login(Request $request, User $user, AuditLogger $audit): RedirectResponse
    {
        $remember = (bool) $request->session()->get('two_factor_remember', false);

        Auth::login($user, $remember);
        $request->session()->forget(['two_factor_user_id', 'two_factor_remember']);
        $request->session()->regenerate();

        $audit->log('auth.login', $user, 'Masuk ke aplikasi setelah verifikasi dua faktor');

        return redirect()->intended(route('dashboard'));
    }
}
