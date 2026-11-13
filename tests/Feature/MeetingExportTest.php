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

    public function test_official_meeting_pdf_view_contains_company_format_sections(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);
        $meeting->load(['creator:id,name', 'approvedBy:id,name', 'participants.user:id,name,job_title', 'actionItems']);

        $html = view('exports.meeting-minutes-pdf', [
            'meeting' => $meeting,
            'contentHtml' => '<h2>Agenda Pembahasan</h2><ol><li>Agenda</li></ol><h2>Hasil Rapat</h2><table><tr><td>Contoh</td></tr></table>',
            'nomor' => '01/I/TI/2026',
            'logoPath' => public_path('company-logo.png'),
            'hasTable' => true,
            'hasResultHeading' => true,
            'hasAgendaHeading' => true,
        ])->render();

        $this->assertStringContainsString('NOTULENSI RAPAT', $html);
        $this->assertStringContainsString('Nomor Notulensi: 01/I/TI/2026', $html);
        $this->assertStringContainsString('Agenda Pembahasan', $html);
        $this->assertStringContainsString('Dibuat Oleh:', $html);
        $this->assertStringContainsString('Disetujui Oleh:', $html);
        $this->assertStringContainsString('Dilarang mengubah format notulensi', $html);
    }

    public function test_pdf_can_be_opened_inline_for_printing(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $response = $this->actingAs($notulis)->get(route('meetings.export.pdf', [
            'meeting' => $meeting->id,
            'inline' => 1,
        ]));

        $response->assertOk();
        $this->assertStringContainsString('inline', (string) $response->headers->get('content-disposition'));
    }

    public function test_official_pdf_view_renders_agenda_from_meeting_field(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
            'agenda' => "Agenda pertama\nAgenda kedua",
        ]);
        $meeting->load(['creator:id,name', 'approvedBy:id,name', 'participants.user:id,name,job_title', 'actionItems', 'division.head:id,name']);

        $html = view('exports.meeting-minutes-pdf', [
            'meeting' => $meeting,
            'contentHtml' => '<table><tr><td>Contoh</td></tr></table>',
            'nomor' => '01/I/TI/2026',
            'logoPath' => public_path('company-logo.png'),
            'hasTable' => true,
            'hasResultHeading' => false,
            'hasAgendaHeading' => false,
        ])->render();

        $this->assertStringContainsString('AGENDA PEMBAHASAN', $html);
        $this->assertStringContainsString('Agenda pertama', $html);
        $this->assertStringContainsString('Agenda kedua', $html);
        $this->assertStringContainsString('HASIL RAPAT', $html);
    }
}
