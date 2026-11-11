<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingFinalizedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_notification_marks_it_read_and_redirects_to_its_target(): void
    {
        $user = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $user->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $user->notifyNow(new MeetingFinalizedNotification($meeting));

        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('meetings.show', $meeting));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
