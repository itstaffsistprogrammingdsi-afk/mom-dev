<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingAutosaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_autosave_updates_content_and_synchronizes_tags(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
            'content' => null,
            'content_text' => null,
        ]);

        $content = [
            'type' => 'doc',
            'content' => [[
                'type' => 'paragraph',
                'content' => [
                    ['type' => 'mention', 'attrs' => ['id' => $notulis->id, 'label' => $notulis->name, 'type' => 'person']],
                    ['type' => 'text', 'text' => ' mencatat hasil autosave.'],
                ],
            ]],
        ];

        $this->actingAs($notulis)
            ->patchJson(route('meetings.autosave', $meeting), [
                'title' => 'Judul Autosave',
                'content' => $content,
            ])
            ->assertOk()
            ->assertJsonStructure(['saved_at']);

        $meeting->refresh();

        $this->assertSame('Judul Autosave', $meeting->title);
        $this->assertStringContainsString('mencatat hasil autosave', $meeting->content_text);
        $this->assertDatabaseHas('tags', [
            'meeting_id' => $meeting->id,
            'type' => 'person',
            'target_id' => $notulis->id,
        ]);
    }

    public function test_unrelated_user_cannot_autosave(): void
    {
        $owner = User::factory()->create(['role' => 'notulis']);
        $other = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($other)
            ->patchJson(route('meetings.autosave', $meeting), ['title' => 'Hack'])
            ->assertForbidden();
    }
}
