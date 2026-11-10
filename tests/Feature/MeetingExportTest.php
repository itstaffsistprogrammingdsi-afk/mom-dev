<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_export_pdf_and_docx(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
            'content_text' => 'Rapat membahas peningkatan layanan.',
        ]);

        $pdf = $this->actingAs($notulis)->get(route('meetings.export.pdf', $meeting));
        $pdf->assertOk();
        $pdf->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $docx = $this->actingAs($notulis)->get(route('meetings.export.word', $meeting));
        $docx->assertOk();
        $docx->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertStringStartsWith('PK', $docx->getContent());
    }

    public function test_unrelated_user_cannot_export_restricted_meeting(): void
    {
        $owner = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($outsider)
            ->get(route('meetings.export.pdf', $meeting))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('meetings.export.word', $meeting))
            ->assertForbidden();
    }
}
