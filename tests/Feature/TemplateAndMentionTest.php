<?php

namespace Tests\Feature;

use App\Enums\DocumentType;
use App\Models\Meeting;
use App\Models\MeetingTemplate;
use App\Models\User;
use App\Services\MeetingContentParser;
use App\Services\TemplateContentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateAndMentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_created_from_proposal_template_uses_template_content(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $factory = app(TemplateContentFactory::class);
        $content = $factory->default(DocumentType::Proposal);

        $template = MeetingTemplate::create([
            'name' => 'Proposal Uji',
            'document_type' => DocumentType::Proposal,
            'meeting_type' => 'other',
            'description' => 'Template proposal uji.',
            'sections' => $factory->sectionTitles($content),
            'content' => $content,
            'is_active' => true,
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.store'), [
                'title' => 'Proposal Digitalisasi',
                'meeting_type' => 'other',
                'meeting_template_id' => $template->id,
                'confidentiality' => 'open',
                'participant_ids' => [],
            ])
            ->assertRedirect();

        $meeting = Meeting::query()->where('title', 'Proposal Digitalisasi')->firstOrFail();

        $this->assertSame(DocumentType::Proposal, $meeting->document_type);
        $this->assertStringContainsString('Ringkasan Proyek', $meeting->content_text);
    }

    public function test_admin_can_update_template_content(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $template = MeetingTemplate::create([
            'name' => 'Template Isi',
            'document_type' => 'meeting',
            'meeting_type' => 'weekly',
            'description' => 'Template untuk uji isi.',
            'content' => ['type' => 'doc', 'content' => []],
            'is_active' => true,
        ]);

        $content = [
            'type' => 'doc',
            'content' => [[
                'type' => 'heading',
                'attrs' => ['level' => 2],
                'content' => [['type' => 'text', 'text' => 'Struktur Baru']],
            ]],
        ];

        $this->actingAs($admin)
            ->patch(route('admin.templates.update', $template), [
                'name' => 'Template Isi',
                'document_type' => 'meeting',
                'meeting_type' => 'weekly',
                'description' => 'Template untuk uji isi.',
                'is_active' => true,
                'content' => $content,
            ])
            ->assertRedirect();

        $template->refresh();

        $this->assertSame('Struktur Baru', $template->sections[0]);
        $this->assertSame('Struktur Baru', $template->content['content'][0]['content'][0]['text']);
    }

    public function test_topic_mention_node_is_parsed_as_topic_tag(): void
    {
        $document = [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'topicMention', 'attrs' => ['id' => 7, 'label' => 'Digitalisasi', 'type' => 'topic']],
                ],
            ]],
        ];

        $parsed = app(MeetingContentParser::class)->parse($document);

        $this->assertSame('topic', $parsed['mentions'][0]['type']);
        $this->assertSame(7, $parsed['mentions'][0]['id']);
    }

    public function test_role_and_access_page_explains_all_roles(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.roles.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Roles/Index')
                ->has('roles', 4)
            );
    }

    public function test_official_meeting_template_matches_company_format(): void
    {
        $content = app(TemplateContentFactory::class)->officialMeeting();
        $json = json_encode($content);

        $this->assertSame('doc', $content['type']);
        $this->assertStringContainsString('Point Diskusi', $json);
        $this->assertStringContainsString('Keterangan', $json);
        $this->assertStringContainsString('Action Plan', $json);
        $this->assertStringContainsString('Skala Prioritas', $json);
        $this->assertStringContainsString('Divisi', $json);
        $this->assertStringContainsString('PIC', $json);
        $this->assertStringContainsString('Deadline', $json);
        $this->assertStringContainsString('Output', $json);
        $this->assertStringContainsString('"type":"table"', $json);
    }
}
