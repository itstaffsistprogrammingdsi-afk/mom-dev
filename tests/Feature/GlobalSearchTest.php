<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\QuickNote;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\QuickNoteMentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_search_returns_only_accessible_meetings(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $visible = Meeting::factory()->create([
            'title' => 'Rapat Anggaran Tahunan',
            'content_text' => 'Pembahasan anggaran tahunan.',
            'confidentiality' => 'open',
        ]);
        Meeting::factory()->create([
            'title' => 'Rapat Anggaran Rahasia',
            'content_text' => 'Pembahasan anggaran rahasia.',
            'confidentiality' => 'restricted',
        ]);

        $this->actingAs($user)
            ->getJson(route('global-search', ['q' => 'anggaran']))
            ->assertOk()
            ->assertJsonCount(1, 'meetings')
            ->assertJsonPath('meetings.0.id', $visible->id);
    }

    public function test_global_search_finds_topics_and_people(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $topic = Topic::factory()->create(['name' => 'Anggaran Operasional', 'status' => 'active']);
        $person = User::factory()->create(['name' => 'Anggota Anggaran', 'is_active' => true]);

        $this->actingAs($user)
            ->getJson(route('global-search', ['q' => 'Anggaran']))
            ->assertOk()
            ->assertJsonPath('topics.0.id', $topic->id)
            ->assertJsonFragment(['id' => $person->id, 'title' => $person->name]);
    }

    public function test_global_search_requires_two_characters(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('global-search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'meetings')
            ->assertJsonCount(0, 'topics')
            ->assertJsonCount(0, 'people');
    }

    public function test_quick_note_notification_respects_disabled_category(): void
    {
        $user = User::factory()->create([
            'notification_preferences' => [
                'email' => true,
                'in_app' => true,
                'categories' => ['quick_note' => false],
            ],
        ]);

        $notification = new QuickNoteMentionNotification(
            new QuickNote(['user_id' => $user->id, 'title' => 'Catatan uji']),
        );

        $this->assertSame([], $notification->via($user));
    }
}
