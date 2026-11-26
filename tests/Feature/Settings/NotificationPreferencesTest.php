<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_notification_preferences(): void
    {
        $user = User::factory()->create([
            'notification_preferences' => [
                'email' => false,
                'in_app' => true,
                'categories' => ['meeting' => true, 'action_item' => false, 'quick_note' => true],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('notification-preferences.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Notifications')
                ->where('preferences.email', false)
                ->where('preferences.in_app', true)
                ->where('preferences.categories.action_item', false)
            );
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('notification-preferences.update'), [
                'email' => false,
                'in_app' => true,
                'categories' => [
                    'meeting' => true,
                    'action_item' => false,
                    'quick_note' => true,
                ],
            ])
            ->assertRedirect();

        $preferences = $user->fresh()->notification_preferences;

        $this->assertFalse($preferences['email']);
        $this->assertFalse($preferences['categories']['action_item']);
        $this->assertFalse($user->fresh()->wantsEmailNotifications('meeting'));
        $this->assertFalse($user->fresh()->wantsInAppNotifications('action_item'));
    }
}
