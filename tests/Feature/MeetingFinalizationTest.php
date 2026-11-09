<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingFinalizedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MeetingFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_requires_pic_and_due_date(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => null,
            'pic_name' => null,
            'due_date' => null,
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.finalize', $meeting))
            ->assertSessionHasErrors('meeting');

        $this->assertSame(MeetingStatus::Draft, $meeting->fresh()->status);
    }

    public function test_finalize_creates_version_and_notifies_participants(): void
    {
        Notification::fake();

        $notulis = User::factory()->create(['role' => 'notulis']);
        $participant = User::factory()->create(['role' => 'member']);

        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $meeting->participants()->create([
            'user_id' => $participant->id,
            'name' => $participant->name,
        ]);

        ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $participant->id,
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.finalize', $meeting))
            ->assertRedirect(route('meetings.show', $meeting));

        $meeting->refresh();

        $this->assertSame(MeetingStatus::Final, $meeting->status);
        $this->assertNotNull($meeting->finalized_at);
        $this->assertDatabaseHas('meeting_versions', [
            'meeting_id' => $meeting->id,
            'reason' => 'finalized',
        ]);

        Notification::assertSentTo($participant, MeetingFinalizedNotification::class);
    }

    public function test_admin_can_restore_meeting_version(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Final->value,
            'confidentiality' => Confidentiality::Open->value,
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Versi lama']]]]],
            'content_text' => 'Versi lama',
        ]);

        $version = $meeting->versions()->create([
            'version' => 1,
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Versi tersimpan']]]]],
            'content_text' => 'Versi tersimpan',
            'reason' => 'finalized',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('meetings.versions.restore', [$meeting, $version]))
            ->assertRedirect();

        $this->assertSame('Versi tersimpan', $meeting->fresh()->content_text);
    }

    public function test_admin_can_lock_and_unlock_final_meeting(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Final->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($admin)
            ->post(route('meetings.lock', $meeting))
            ->assertRedirect();

        $this->assertSame(MeetingStatus::Locked, $meeting->fresh()->status);

        $this->actingAs($admin)
            ->post(route('meetings.unlock', $meeting))
            ->assertRedirect();

        $this->assertSame(MeetingStatus::Final, $meeting->fresh()->status);
    }
}
