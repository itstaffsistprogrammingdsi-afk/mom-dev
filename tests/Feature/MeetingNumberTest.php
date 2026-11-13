<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_number_is_generated_automatically_on_create(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);

        $this->actingAs($notulis)->post(route('meetings.store'), [
            'title' => 'Rapat Nomor Otomatis',
            'meeting_type' => 'weekly',
            'confidentiality' => 'open',
            'scheduled_at' => '2026-11-13 10:00',
            'agenda' => 'Agenda rapat',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
        ])->assertRedirect();

        $meeting = Meeting::query()->where('title', 'Rapat Nomor Otomatis')->firstOrFail();

        $this->assertSame('13/XI/UMUM/2026', $meeting->nomor_notulensi);
        $this->assertSame('sheet', $meeting->editor_mode);
    }

    public function test_custom_number_and_sheet_mode_are_preserved(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);

        $this->actingAs($notulis)->post(route('meetings.store'), [
            'title' => 'Rapat Nomor Khusus',
            'nomor_notulensi' => 'Khusus/01/2026',
            'editor_mode' => 'sheet',
            'meeting_type' => 'weekly',
            'confidentiality' => 'open',
            'scheduled_at' => '2026-11-13 10:00',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
        ])->assertRedirect();

        $meeting = Meeting::query()->where('title', 'Rapat Nomor Khusus')->firstOrFail();

        $this->assertSame('Khusus/01/2026', $meeting->nomor_notulensi);
        $this->assertSame('sheet', $meeting->editor_mode);
    }
}
