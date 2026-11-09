<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\ActionItem;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\Topic;
use App\Models\User;
use App\Services\MeetingTagSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MeetingAccessTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role, ?Division $division = null): User
    {
        return User::factory()->create([
            'role' => $role,
            'division_id' => $division?->id,
        ]);
    }

    private function meeting(array $attributes = []): Meeting
    {
        return Meeting::factory()->create($attributes);
    }

    public function test_admin_can_view_secret_meeting(): void
    {
        $admin = $this->userWithRole('admin');
        $meeting = $this->meeting(['confidentiality' => Confidentiality::Secret->value]);

        $this->actingAs($admin)
            ->get(route('meetings.show', $meeting))
            ->assertOk();
    }

    public function test_participant_can_view_restricted_meeting(): void
    {
        $division = Division::factory()->create();
        $participant = $this->userWithRole('member', $division);
        $meeting = $this->meeting([
            'confidentiality' => Confidentiality::Restricted->value,
            'division_id' => $division->id,
        ]);

        $meeting->participants()->create(['user_id' => $participant->id, 'name' => $participant->name]);

        $this->actingAs($participant)
            ->get(route('meetings.show', $meeting))
            ->assertOk();
    }

    public function test_unrelated_user_cannot_view_restricted_meeting(): void
    {
        $division = Division::factory()->create();
        $outsider = $this->userWithRole('member', $division);
        $meeting = $this->meeting(['confidentiality' => Confidentiality::Restricted->value]);

        $this->actingAs($outsider)
            ->get(route('meetings.show', $meeting))
            ->assertForbidden();
    }

    public function test_open_meeting_is_visible_to_every_authenticated_user(): void
    {
        $user = $this->userWithRole('reader');
        $meeting = $this->meeting(['confidentiality' => Confidentiality::Open->value]);

        $this->actingAs($user)
            ->get(route('meetings.show', $meeting))
            ->assertOk();
    }

    public function test_related_page_lists_tagged_and_participating_meetings(): void
    {
        $division = Division::factory()->create();
        $user = $this->userWithRole('member', $division);

        $tagged = $this->meeting([
            'confidentiality' => Confidentiality::Restricted->value,
            'status' => MeetingStatus::Draft->value,
        ]);
        $tagged->tags()->create(['type' => 'person', 'target_id' => $user->id, 'target_name' => $user->name]);

        $tooSecret = $this->meeting(['confidentiality' => Confidentiality::Secret->value]);
        $tooSecret->tags()->create(['type' => 'person', 'target_id' => $user->id, 'target_name' => $user->name]);

        $this->actingAs($user)
            ->get(route('related.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Related/Index')
                ->has('meetings', 1)
                ->where('meetings.0.id', $tagged->id)
            );
    }

    public function test_creator_can_finalize_but_cannot_edit_after_final(): void
    {
        $notulis = $this->userWithRole('notulis');
        $meeting = $this->meeting([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
            'status' => MeetingStatus::Draft->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.finalize', $meeting))
            ->assertRedirect(route('meetings.show', $meeting));

        $this->assertSame(MeetingStatus::Final, $meeting->fresh()->status);

        $this->actingAs($notulis)
            ->get(route('meetings.edit', $meeting))
            ->assertForbidden();
    }

    public function test_tags_are_synchronized_from_editor_mentions(): void
    {
        $division = Division::factory()->create();
        $user = $this->userWithRole('member', $division);
        $topic = Topic::factory()->create();
        $meeting = $this->meeting(['created_by' => $user->id]);

        $document = [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'mention', 'attrs' => ['id' => $user->id, 'label' => $user->name, 'type' => 'person']],
                    ['type' => 'text', 'text' => ' dan '],
                    ['type' => 'mention', 'attrs' => ['id' => $division->id, 'label' => $division->name, 'type' => 'division']],
                    ['type' => 'mention', 'attrs' => ['id' => $topic->id, 'label' => $topic->name, 'type' => 'topic']],
                ],
            ]],
        ];

        app(MeetingTagSynchronizer::class)->sync($meeting, $document, $user->id);

        $this->assertSame(3, $meeting->tags()->count());
        $this->assertDatabaseHas('tags', ['meeting_id' => $meeting->id, 'type' => 'topic', 'target_id' => $topic->id]);
    }

    public function test_pic_can_update_their_action_item_status(): void
    {
        $division = Division::factory()->create();
        $pic = $this->userWithRole('member', $division);
        $meeting = $this->meeting([
            'confidentiality' => Confidentiality::Open->value,
            'meeting_type' => MeetingType::Weekly->value,
        ]);
        $task = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $pic->id,
            'status' => 'new',
        ]);

        $this->actingAs($pic)
            ->patch(route('action-items.status', $task), ['status' => 'in_progress'])
            ->assertRedirect();

        $this->assertSame('in_progress', $task->fresh()->status->value);
    }

    public function test_other_member_cannot_update_action_item_status(): void
    {
        $division = Division::factory()->create();
        $pic = $this->userWithRole('member', $division);
        $other = $this->userWithRole('member', $division);
        $meeting = $this->meeting(['confidentiality' => Confidentiality::Open->value]);
        $task = ActionItem::factory()->create([
            'meeting_id' => $meeting->id,
            'pic_id' => $pic->id,
            'status' => 'new',
        ]);

        $this->actingAs($other)
            ->patch(route('action-items.status', $task), ['status' => 'done'])
            ->assertForbidden();
    }
}
