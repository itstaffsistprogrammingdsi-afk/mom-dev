<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_two_factor_must_complete_challenge_before_login(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => 'password',
        ]);
        $secret = app(Google2FA::class)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['recovery-one', 'recovery-two'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.challenge'));

        $this->assertGuest();

        $this->post(route('two-factor.challenge.store'), [
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_recovery_code_can_be_used_once(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => 'password',
        ]);
        $secret = app(Google2FA::class)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['recovery-one', 'recovery-two'],
            'two_factor_confirmed_at' => now(),
        ])->save();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post(route('two-factor.challenge.store'), ['code' => 'recovery-one'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertSame(['recovery-two'], $user->fresh()->two_factor_recovery_codes);
    }
}
