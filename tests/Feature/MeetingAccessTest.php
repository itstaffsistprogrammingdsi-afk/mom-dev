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

    public function test_notulis_cannot_change_meetings_they_cannot_view(): void
    {
        $outsider = $this->userWithRole('notulis');
        $meeting = $this->meeting([
            'confidentiality' => Confidentiality::Restricted->value,
            'status' => MeetingStatus::Draft->value,
        ]);

        $this->actingAs($outsider)
            ->patch(route('meetings.autosave', $meeting), ['title' => 'Perubahan tidak sah'])
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post(route('meetings.finalize', $meeting))
            ->assertForbidden();

        $this->assertNotSame('Perubahan tidak sah', $meeting->fresh()->title);
        $this->assertSame(MeetingStatus::Draft, $meeting->fresh()->status);
    }

    public function test_meeting_search_returns_only_visible_results(): void
    {
        $viewer = $this->userWithRole('member');

        $visible = $this->meeting([
            'title' => 'Rapat Anggaran Tahunan',
            'confidentiality' => Confidentiality::Open->value,
        ]);
        $this->meeting([
            'title' => 'Rapat Anggaran Rahasia',
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($viewer)
            ->get(route('meetings.index', ['search' => 'Anggaran']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Meetings/Index')
                ->has('meetings.data', 1)
                ->where('meetings.data.0.id', $visible->id)
            );
    }

    public function test_topic_page_hides_decisions_and_summaries_from_inaccessible_meetings(): void
    {
        $viewer = $this->userWithRole('member');
        $topic = Topic::factory()->create();

        $visible = $this->meeting(['confidentiality' => Confidentiality::Open->value]);
        $hidden = $this->meeting(['confidentiality' => Confidentiality::Restricted->value]);

        $visible->decisions()->create(['content' => 'Keputusan terbuka', 'topic_id' => $topic->id]);
        $hidden->decisions()->create(['content' => 'Keputusan rahasia', 'topic_id' => $topic->id]);

        $topic->summaries()->create([
            'scope' => 'topic',
            'meeting_id' => null,
            'content' => ['ringkasan' => 'Ringkasan terbuka'],
            'version' => 1,
            'status' => 'approved',
            'generation_status' => 'completed',
            'language' => 'id',
        ]);
        $topic->summaries()->create([
            'scope' => 'meeting',
            'meeting_id' => $hidden->id,
            'content' => ['ringkasan' => 'Ringkasan rahasia'],
            'version' => 1,
            'status' => 'approved',
            'generation_status' => 'completed',
            'language' => 'id',
        ]);

        $this->actingAs($viewer)
            ->get(route('topics.show', $topic))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Topics/Show')
                ->has('decisions', 1)
                ->where('decisions.0.content', 'Keputusan terbuka')
                ->has('summaries', 1)
                ->where('summaries.0.content.ringkasan', 'Ringkasan terbuka')
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

    public function test_non_creator_notulis_confidentiality_change_is_ignored_by_autosave(): void
    {
        $creator = $this->userWithRole('notulis');
        $other = $this->userWithRole('notulis');
        $meeting = $this->meeting([
            'created_by' => $creator->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($other)
            ->patchJson(route('meetings.autosave', $meeting), [
                'title' => 'Judul tetap tersimpan',
                'confidentiality' => Confidentiality::Secret->value,
            ])
            ->assertOk();

        $meeting->refresh();
        $this->assertSame('Judul tetap tersimpan', $meeting->title);
        $this->assertSame(Confidentiality::Open, $meeting->confidentiality);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'meeting.confidentiality_changed',
            'meeting_id' => $meeting->id,
        ]);
    }

    public function test_creator_can_change_confidentiality_and_change_is_audited(): void
    {
        $creator = $this->userWithRole('notulis');
        $meeting = $this->meeting([
            'created_by' => $creator->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($creator)
            ->patchJson(route('meetings.autosave', $meeting), [
                'confidentiality' => Confidentiality::Restricted->value,
            ])
            ->assertOk();

        $this->assertSame(Confidentiality::Restricted, $meeting->fresh()->confidentiality);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meeting.confidentiality_changed',
            'meeting_id' => $meeting->id,
        ]);
    }

    public function test_non_creator_cannot_change_confidentiality_via_update(): void
    {
        $creator = $this->userWithRole('notulis');
        $other = $this->userWithRole('notulis');
        $meeting = $this->meeting([
            'created_by' => $creator->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($other)
            ->patch(route('meetings.update', $meeting), [
                'title' => $meeting->title,
                'confidentiality' => Confidentiality::Secret->value,
                'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            ])
            ->assertSessionHasErrors('confidentiality');

        $this->assertSame(Confidentiality::Open, $meeting->fresh()->confidentiality);
    }

    public function test_searchable_array_only_contains_text_fields(): void
    {
        $meeting = $this->meeting([
            'title' => 'Rapat Anggaran',
            'agenda' => 'Agenda rapat',
            'content_text' => 'Isi pembahasan',
        ]);

        $this->assertSame(['title', 'agenda', 'content_text'], array_keys($meeting->toSearchableArray()));
    }

    public function test_topic_index_counts_only_visible_decisions(): void
    {
        $viewer = $this->userWithRole('member');
        $topic = Topic::factory()->create(['status' => 'active']);

        $visible = $this->meeting(['confidentiality' => Confidentiality::Open->value]);
        $hidden = $this->meeting(['confidentiality' => Confidentiality::Restricted->value]);

        $visible->decisions()->create(['content' => 'Keputusan terbuka', 'topic_id' => $topic->id]);
        $hidden->decisions()->create(['content' => 'Keputusan rahasia', 'topic_id' => $topic->id]);

        $this->actingAs($viewer)
            ->get(route('topics.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Topics/Index')
                ->where('topics.0.decisions_count', 1)
            );
    }

    public function test_people_page_does_not_expose_sensitive_user_fields(): void
    {
        $viewer = $this->userWithRole('member');
        $person = $this->userWithRole('member');

        $this->actingAs($viewer)
            ->get(route('people.show', $person))
            ->assertInertia(fn (Assert $page) => $page
                ->component('People/Show')
                ->where('person.id', $person->id)
                ->missing('person.google_id')
                ->missing('person.last_login_at')
                ->missing('person.notification_preferences')
            );
    }
}
