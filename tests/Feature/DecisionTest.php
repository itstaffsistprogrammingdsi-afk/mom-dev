<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\Decision;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DecisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_update_and_delete_decision(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
            'status' => MeetingStatus::Draft->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('decisions.store', $meeting), [
                'content' => 'Menyetujui anggaran triwulan IV.',
                'decided_at' => now()->toDateString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $decision = Decision::query()->where('meeting_id', $meeting->id)->firstOrFail();

        $this->actingAs($notulis)
            ->patch(route('decisions.update', $decision), [
                'content' => 'Menyetujui anggaran triwulan IV dengan efisiensi.',
            ])
            ->assertRedirect();

        $this->assertSame('Menyetujui anggaran triwulan IV dengan efisiensi.', $decision->fresh()->content);

        $this->actingAs($notulis)
            ->delete(route('decisions.destroy', $decision))
            ->assertRedirect();

        $this->assertDatabaseMissing('decisions', ['id' => $decision->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'decision.created', 'meeting_id' => $meeting->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'decision.updated', 'meeting_id' => $meeting->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'decision.deleted', 'meeting_id' => $meeting->id]);
    }

    public function test_outsider_cannot_manage_decision(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($outsider)
            ->post(route('decisions.store', $meeting), ['content' => 'Tidak boleh.'])
            ->assertForbidden();

        $decision = Decision::factory()->create([
            'meeting_id' => $meeting->id,
            'content' => 'Keputusan asli.',
        ]);

        $this->actingAs($outsider)
            ->patch(route('decisions.update', $decision), ['content' => 'Diubah.'])
            ->assertForbidden();

        $this->actingAs($outsider)
            ->delete(route('decisions.destroy', $decision))
            ->assertForbidden();
    }
}
