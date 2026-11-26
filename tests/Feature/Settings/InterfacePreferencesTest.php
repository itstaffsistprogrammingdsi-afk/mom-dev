<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InterfacePreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_interface_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('interface-preferences.update'), [
                'locale' => 'en',
                'appearance' => 'dark',
                'timezone' => 'Asia/Jayapura',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('en', $user->locale);
        $this->assertSame('dark', $user->appearance);
        $this->assertSame('Asia/Jayapura', $user->timezone);
    }

    public function test_interface_preferences_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('interface-preferences.update'), [
                'locale' => 'fr',
                'appearance' => 'neon',
                'timezone' => 'Mars/Olympus',
            ])
            ->assertSessionHasErrors(['locale', 'appearance', 'timezone']);
    }

    public function test_interface_preferences_are_shared_with_inertia(): void
    {
        $user = User::factory()->create([
            'locale' => 'en',
            'appearance' => 'dark',
            'timezone' => 'Asia/Jakarta',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('preferences.locale', 'en')
                ->where('preferences.appearance', 'dark')
                ->where('preferences.timezone', 'Asia/Jakarta')
            );
    }
}
