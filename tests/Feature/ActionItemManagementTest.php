<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActionItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_create_update_and_delete_action_item(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('action-items.store', $meeting), [
                'title' => 'Susun laporan',
                'due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertRedirect();

        $item = ActionItem::query()->where('meeting_id', $meeting->id)->firstOrFail();

        $this->actingAs($notulis)
            ->patch(route('action-items.update', $item), [
                'title' => 'Susun laporan final',
                'due_date' => now()->addDays(2)->toDateString(),
                'status' => 'in_progress',
            ])
            ->assertRedirect();

        $this->assertSame('Susun laporan final', $item->fresh()->title);

        $this->actingAs($notulis)
            ->delete(route('action-items.destroy', $item))
            ->assertRedirect();

        $this->assertDatabaseMissing('action_items', ['id' => $item->id]);
    }

    public function test_updating_status_to_done_sets_completed_at(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);
        $item = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'status' => 'new',
            'completed_at' => null,
        ]);

        $this->actingAs($notulis)
            ->patch(route('action-items.update', $item), [
                'title' => $item->title,
                'status' => 'done',
            ])
            ->assertRedirect();

        $this->assertNotNull($item->fresh()->completed_at);

        $this->actingAs($notulis)
            ->patch(route('action-items.update', $item), [
                'title' => $item->title,
                'status' => 'in_progress',
            ])
            ->assertRedirect();

        $this->assertNull($item->fresh()->completed_at);
    }

    public function test_action_item_due_today_is_not_overdue(): void
    {
        $item = ActionItem::factory()->create([
            'due_date' => today(),
            'status' => 'new',
        ]);

        $this->assertFalse($item->is_overdue);
    }

    public function test_board_view_returns_grouped_action_items(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $meeting = Meeting::factory()->create([
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'status' => 'new',
            'due_date' => now()->addDays(3),
        ]);

        $this->actingAs($user)
            ->get(route('action-items.index', ['view' => 'board']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('ActionItems/Index')
                ->where('view', 'board')
                ->has('board.new', 1)
            );
    }

    public function test_unrelated_member_cannot_delete_action_item(): void
    {
        $owner = User::factory()->create(['role' => 'notulis']);
        $member = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);
        $item = ActionItem::factory()->create(['meeting_id' => $meeting->id]);

        $this->actingAs($member)
            ->delete(route('action-items.destroy', $item))
            ->assertForbidden();
    }
}
