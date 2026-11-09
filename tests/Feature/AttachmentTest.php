<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_upload_file_and_download_it(): void
    {
        Storage::fake('local');

        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $file = UploadedFile::fake()->create('notulen-lampiran.pdf', 100, 'application/pdf');

        $this->actingAs($notulis)
            ->post(route('attachments.store', $meeting), ['file' => $file])
            ->assertRedirect();

        $attachment = $meeting->attachments()->firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $this->actingAs($notulis)
            ->get(route('attachments.download', $attachment))
            ->assertDownload('notulen-lampiran.pdf');
    }

    public function test_link_attachment_can_be_added(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('attachments.store', $meeting), [
                'url' => 'https://example.com/dokumen',
                'name' => 'Dokumen pendukung',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('attachments', [
            'meeting_id' => $meeting->id,
            'kind' => 'link',
            'original_name' => 'Dokumen pendukung',
        ]);
    }

    public function test_outsider_cannot_download_attachment_of_restricted_meeting(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);

        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $attachment = $meeting->attachments()->create([
            'uploaded_by' => $owner->id,
            'kind' => 'file',
            'disk' => 'local',
            'path' => 'attachments/test.pdf',
            'original_name' => 'test.pdf',
            'size' => 10,
        ]);

        Storage::disk('local')->put('attachments/test.pdf', 'dummy');

        $this->actingAs($outsider)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }
}
