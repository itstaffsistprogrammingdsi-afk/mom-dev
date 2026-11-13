<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\ActionItem;
use App\Models\ActionItemFeedback;
use App\Models\Meeting;
use App\Models\User;
use App\Notifications\ActionItemResponseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActionItemResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_a_task_sets_response_deadline_and_notifies_owner_and_tagged_members(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'notulis']);
        $tagged = User::factory()->create(['role' => 'member']);
        $pic = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);

        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);
        $meeting->tags()->create([
            'type' => 'person',
            'target_id' => $tagged->id,
            'target_name' => $tagged->name,
        ]);

        $item = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $pic->id,
            'status' => 'new',
            'response_hours' => 24,
        ]);

        $this->actingAs($pic)
            ->post(route('action-items.feedback.store', $item), [
                'body' => 'Mulai dikerjakan, dokumen pendukung sudah saya terima.',
                'response_status' => 'in_progress',
                'status' => 'in_progress',
                'response_hours' => 4,
            ])
            ->assertRedirect();

        $item->refresh();

        $this->assertSame('in_progress', $item->status->value);
        $this->assertSame(4, $item->response_hours);
        $this->assertNotNull($item->response_due_at);
        $this->assertNotNull($item->first_responded_at);
        $this->assertDatabaseHas('action_item_feedback', [
            'action_item_id' => $item->id,
            'user_id' => $pic->id,
            'response_status' => 'in_progress',
        ]);

        Notification::assertSentTo($owner, ActionItemResponseNotification::class);
        Notification::assertSentTo($tagged, ActionItemResponseNotification::class);
        Notification::assertNotSentTo($stranger, ActionItemResponseNotification::class);
        Notification::assertNotSentTo($pic, ActionItemResponseNotification::class);
    }

    public function test_response_photo_can_be_uploaded_and_viewed(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create(['role' => 'notulis']);
        $pic = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);

        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);
        $meeting->tags()->create([
            'type' => 'person',
            'target_id' => $pic->id,
            'target_name' => $pic->name,
        ]);

        $item = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $pic->id,
            'status' => 'new',
        ]);

        $this->actingAs($pic)
            ->post(route('action-items.feedback.store', $item), [
                'body' => 'Kondisi lapangan sudah dicek.',
                'response_status' => 'acknowledged',
                'photo' => UploadedFile::fake()->image('kondisi.jpg', 800, 600),
            ])
            ->assertRedirect();

        $feedback = ActionItemFeedback::query()->firstOrFail();

        $this->assertNotNull($feedback->photo_path);
        Storage::disk('public')->assertExists($feedback->photo_path);
        $this->assertSame(
            route('action-items.feedback.photo', [$item, $feedback]),
            $feedback->photo_url,
        );

        $this->actingAs($owner)
            ->get(route('action-items.feedback.photo', [$item, $feedback]))
            ->assertOk();

        $this->actingAs($stranger)
            ->get(route('action-items.feedback.photo', [$item, $feedback]))
            ->assertForbidden();
    }
}
