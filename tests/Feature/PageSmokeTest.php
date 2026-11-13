<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\Meeting;
use App\Models\MeetingTemplate;
use App\Models\QuickNote;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->actingAs(User::query()->where('email', 'admin@mom.test')->firstOrFail());
    }

    public function test_main_pages_render_successfully(): void
    {
        $routes = [
            '/dashboard',
            '/notulen',
            '/notulen/buat',
            '/quick-notes',
            '/quick-notes/buat',
            '/terkait-saya',
            '/topik',
            '/tugas',
            '/notifikasi',
            '/admin/divisi',
            '/admin/topik',
            '/admin/template',
            '/admin/pengguna',
            '/admin/peran',
            '/admin/audit',
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertOk();
        }
    }

    public function test_meeting_and_topic_pages_render_successfully(): void
    {
        foreach (Meeting::all() as $meeting) {
            $this->get(route('meetings.show', $meeting))->assertOk();
        }

        $meeting = Meeting::query()->where('status', 'draft')->firstOrFail();
        $this->get(route('meetings.edit', $meeting))->assertOk();

        $topic = Topic::query()->firstOrFail();
        $this->get(route('topics.show', $topic))->assertOk();

        $division = Division::query()->firstOrFail();
        $this->get(route('divisions.show', $division))->assertOk();

        $person = User::query()->firstOrFail();
        $this->get(route('people.show', $person))->assertOk();

        $template = MeetingTemplate::query()->firstOrFail();
        $this->get(route('admin.templates.edit', $template))->assertOk();

        $quickNote = QuickNote::query()->create([
            'user_id' => User::query()->firstOrFail()->id,
            'title' => 'Catatan uji',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            'content_text' => 'Catatan uji',
        ]);

        $this->get(route('quick-notes.show', $quickNote))->assertOk();
        $this->get(route('quick-notes.edit', $quickNote))->assertOk();
    }

    public function test_creating_meeting_extracts_tags_from_editor_content(): void
    {
        $manager = User::query()->where('email', 'manager@mom.test')->firstOrFail();
        $division = $manager->division;
        $topic = Topic::query()->firstOrFail();

        $response = $this->post(route('meetings.store'), [
            'title' => 'Rapat Uji Coba Tag',
            'meeting_type' => 'weekly',
            'confidentiality' => 'division',
            'agenda' => 'Uji coba ekstraksi tag.',
            'participant_ids' => [$manager->id],
            'content' => [
                'type' => 'doc',
                'content' => [[
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'mention', 'attrs' => ['id' => $manager->id, 'label' => $manager->name, 'type' => 'person']],
                        ['type' => 'text', 'text' => ' dan '],
                        ['type' => 'mention', 'attrs' => ['id' => $division->id, 'label' => $division->name, 'type' => 'division']],
                        ['type' => 'mention', 'attrs' => ['id' => $topic->id, 'label' => $topic->name, 'type' => 'topic']],
                    ],
                ]],
            ],
        ]);

        $meeting = Meeting::query()->where('title', 'Rapat Uji Coba Tag')->firstOrFail();

        $response->assertRedirect(route('meetings.show', $meeting));
        $this->assertSame(3, $meeting->tags()->count());
        $this->assertSame(1, $meeting->participants()->count());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meeting.created',
            'meeting_id' => $meeting->id,
        ]);
    }
}
