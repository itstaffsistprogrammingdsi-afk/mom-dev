<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MeetingSignatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_upload_signature_and_it_is_audited(): void
    {
        Storage::fake('local');

        $creator = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($creator)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'creator']), [
                'signature' => UploadedFile::fake()->image('tanda-tangan.png', 300, 120),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $signature = $meeting->signatures()->where('role', 'creator')->firstOrFail();

        Storage::disk('local')->assertExists($signature->signature_path);
        $this->assertSame($creator->name, $signature->name);
        $this->assertSame($creator->id, $signature->signed_by);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meeting.signature_uploaded',
            'meeting_id' => $meeting->id,
        ]);
    }

    public function test_other_notulis_cannot_upload_creator_signature(): void
    {
        Storage::fake('local');

        $creator = User::factory()->create(['role' => 'notulis']);
        $other = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($other)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'creator']), [
                'signature' => UploadedFile::fake()->image('tanda-tangan.png'),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('meeting_signatures', 0);
    }

    public function test_approver_or_admin_can_sign_approver_slot(): void
    {
        Storage::fake('local');

        $creator = User::factory()->create(['role' => 'notulis']);
        $approver = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'approved_by' => $creator->id,
            'approver_id' => $approver->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($approver)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'approver']), [
                'signature' => UploadedFile::fake()->image('approver.png'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_signatures', [
            'meeting_id' => $meeting->id,
            'role' => 'approver',
            'signed_by' => $approver->id,
        ]);

        $this->actingAs($creator)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'approver']), [
                'signature' => UploadedFile::fake()->image('creator.png'),
            ])
            ->assertForbidden();
    }

    public function test_signature_cannot_be_changed_after_meeting_is_locked(): void
    {
        Storage::fake('local');

        $creator = User::factory()->create(['role' => 'notulis']);
        $approver = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'approver_id' => $approver->id,
            'status' => 'locked',
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($approver)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'approver']), [
                'signature' => UploadedFile::fake()->image('approver.png'),
            ])
            ->assertSessionHasErrors('signature');

        $this->assertDatabaseCount('meeting_signatures', 0);
    }

    public function test_signature_is_private_and_only_visible_to_meeting_viewers(): void
    {
        Storage::fake('local');

        $creator = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($creator)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'creator']), [
                'signature' => UploadedFile::fake()->image('tanda-tangan.png'),
            ]);

        $signature = $meeting->signatures()->firstOrFail();

        Storage::disk('local')->assertExists($signature->signature_path);
        Storage::disk('public')->assertMissing($signature->signature_path);

        $this->actingAs($creator)
            ->get(route('meeting-signatures.show', $signature))
            ->assertOk();

        $this->actingAs($outsider)
            ->get(route('meeting-signatures.show', $signature))
            ->assertForbidden();
    }

    public function test_signature_can_be_replaced_and_removed(): void
    {
        Storage::fake('local');

        $creator = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($creator)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'creator']), [
                'signature' => UploadedFile::fake()->image('first.png'),
            ]);

        $firstPath = $meeting->signatures()->firstOrFail()->signature_path;

        $this->actingAs($creator)
            ->post(route('meeting-signatures.store', ['meeting' => $meeting, 'role' => 'creator']), [
                'signature' => UploadedFile::fake()->image('second.png'),
            ]);

        $secondPath = $meeting->signatures()->firstOrFail()->signature_path;

        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('local')->assertExists($secondPath);

        $this->actingAs($creator)
            ->delete(route('meeting-signatures.destroy', ['meeting' => $meeting, 'role' => 'creator']))
            ->assertRedirect();

        $this->assertDatabaseCount('meeting_signatures', 0);
        Storage::disk('local')->assertMissing($secondPath);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'meeting.signature_removed',
            'meeting_id' => $meeting->id,
        ]);
    }
}
