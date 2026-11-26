<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Mail\MeetingSharedMail;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MeetingShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_share_document_to_email(): void
    {
        Mail::fake();

        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.share', $meeting), [
                'recipients' => 'satu@example.com, dua@example.com',
                'message' => 'Mohon ditinjau.',
                'include_pdf' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(MeetingSharedMail::class, 2);
        Mail::assertSent(MeetingSharedMail::class, fn (MeetingSharedMail $mail) => $mail->hasTo('satu@example.com'));
        Mail::assertSent(MeetingSharedMail::class, fn (MeetingSharedMail $mail) => $mail->hasTo('dua@example.com'));
    }

    public function test_share_can_include_pdf_attachment(): void
    {
        Mail::fake();

        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
            'content_text' => 'Ringkasan rapat untuk lampiran.',
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.share', $meeting), [
                'recipients' => 'satu@example.com',
                'include_pdf' => true,
            ])
            ->assertRedirect();

        Mail::assertSent(MeetingSharedMail::class, function (MeetingSharedMail $mail) {
            return $mail->hasTo('satu@example.com') && count($mail->attachments()) === 1;
        });
    }

    public function test_restricted_meeting_requires_share_confirmation(): void
    {
        Mail::fake();

        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('meetings.share', $meeting), [
                'recipients' => 'satu@example.com',
                'include_pdf' => false,
            ])
            ->assertSessionHasErrors('confirm_share');

        Mail::assertNothingSent();
    }

    public function test_reader_cannot_share_document(): void
    {
        Mail::fake();

        $reader = User::factory()->create(['role' => 'reader']);
        $meeting = Meeting::factory()->create([
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($reader)
            ->post(route('meetings.share', $meeting), [
                'recipients' => 'satu@example.com',
                'include_pdf' => false,
            ])
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_unrelated_user_cannot_share_restricted_meeting(): void
    {
        $owner = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($outsider)
            ->post(route('meetings.share', $meeting), [
                'recipients' => 'satu@example.com',
                'include_pdf' => false,
            ])
            ->assertForbidden();
    }

    public function test_non_creator_notulis_cannot_share_secret_meeting(): void
    {
        Mail::fake();

        $owner = User::factory()->create(['role' => 'notulis']);
        $participant = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'confidentiality' => Confidentiality::Secret->value,
        ]);
        $meeting->participants()->create([
            'user_id' => $participant->id,
            'name' => $participant->name,
            'email' => $participant->email,
        ]);

        $this->actingAs($participant)
            ->post(route('meetings.share', $meeting), [
                'recipients' => 'satu@example.com',
                'include_pdf' => false,
                'confirm_share' => true,
            ])
            ->assertForbidden();

        Mail::assertNothingSent();
    }

    public function test_creator_can_share_secret_meeting_with_confirmation(): void
    {
        Mail::fake();

        $creator = User::factory()->create(['role' => 'notulis']);
        $recipient = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Secret->value,
        ]);
        $meeting->participants()->create([
            'user_id' => $recipient->id,
            'name' => $recipient->name,
            'email' => $recipient->email,
        ]);

        $this->actingAs($creator)
            ->post(route('meetings.share', $meeting), [
                'recipients' => $recipient->email,
                'include_pdf' => false,
                'confirm_share' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Mail::assertSent(MeetingSharedMail::class, 1);
    }

    public function test_restricted_meeting_rejects_recipient_without_access(): void
    {
        Mail::fake();

        $creator = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($creator)
            ->post(route('meetings.share', $meeting), [
                'recipients' => $outsider->email,
                'include_pdf' => false,
                'confirm_share' => true,
            ])
            ->assertSessionHasErrors('recipients');

        Mail::assertNothingSent();
    }
}
