<?php

namespace Tests\Feature;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\ActionItemDueSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ActionItemReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_h1_reminder_is_sent_when_scheduler_runs_on_due_date(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $item = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $user->id,
            'due_date' => today(),
            'status' => 'new',
        ]);

        $this->artisan('mom:remind-action-items')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ActionItemDueSoonNotification::class,
            fn (ActionItemDueSoonNotification $notification) => $notification->kind === 'h1' && $notification->actionItem->id === $item->id,
        );

        $item->refresh();
        $this->assertNotNull($item->reminder_h1_sent_at);
        $this->assertNotNull($item->reminder_h3_sent_at);
    }

    public function test_h3_reminder_catches_up_when_exact_day_was_missed(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        $item = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $user->id,
            'due_date' => today()->addDays(2),
            'status' => 'new',
        ]);

        $this->artisan('mom:remind-action-items')->assertSuccessful();

        Notification::assertSentTo(
            $user,
            ActionItemDueSoonNotification::class,
            fn (ActionItemDueSoonNotification $notification) => $notification->kind === 'h3' && $notification->actionItem->id === $item->id,
        );

        $this->assertNotNull($item->fresh()->reminder_h3_sent_at);
    }

    public function test_reminders_are_not_sent_twice(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $meeting = Meeting::factory()->create(['created_by' => $user->id]);
        ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $user->id,
            'due_date' => today()->addDay(),
            'status' => 'new',
        ]);

        $this->artisan('mom:remind-action-items')->assertSuccessful();
        $this->artisan('mom:remind-action-items')->assertSuccessful();

        Notification::assertSentToTimes($user, ActionItemDueSoonNotification::class, 1);
    }
}
