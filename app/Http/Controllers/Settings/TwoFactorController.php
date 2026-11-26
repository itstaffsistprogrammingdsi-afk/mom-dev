<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $enabled = $user->hasTwoFactorEnabled();
        $secret = $request->session()->get('two_factor_setup_secret');
        $google2fa = app(Google2FA::class);

        if (! $enabled && ! $secret) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('two_factor_setup_secret', $secret);
        }

        return Inertia::render('settings/TwoFactor', [
            'enabled' => $enabled,
            'secret' => $enabled ? null : $secret,
            'otpauthUrl' => $enabled ? null : $google2fa->getQRCodeUrl(
                (string) config('app.name'),
                $user->email,
                (string) $secret,
            ),
            'recoveryCodes' => $request->session()->get('recovery_codes', []),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $secret = (string) $request->session()->get('two_factor_setup_secret', '');

        if ($secret === '' || ! app(Google2FA::class)->verifyKey($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => 'Kode autentikasi tidak valid. Coba lagi.',
            ]);
        }

        $recoveryCodes = collect(range(1, 8))
            ->map(fn () => Str::random(10))
            ->all();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor_setup_secret');

        return redirect()->route('two-factor.show')
            ->with('success', 'Autentikasi dua faktor aktif.')
            ->with('recovery_codes', $recoveryCodes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $request->session()->forget('two_factor_setup_secret');

        return back()->with('success', 'Autentikasi dua faktor dinonaktifkan.');
    }
}
