<?php

namespace Tests\Feature;

use App\Enums\AiSummaryStatus;
use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\AiSummary;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_generate_mock_summary(): void
    {
        config(['ai.driver' => 'mock']);

        $user = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $user->id,
            'confidentiality' => Confidentiality::Open->value,
            'status' => MeetingStatus::Draft->value,
            'content_text' => 'Rapat menyepakati efisiensi anggaran. Tim akan menyusun laporan mingguan.',
        ]);

        $this->actingAs($user)
            ->post(route('meetings.ai.generate', $meeting))
            ->assertRedirect();

        $summary = AiSummary::query()->where('meeting_id', $meeting->id)->latest('id')->firstOrFail();

        $this->assertSame('completed', $summary->generation_status);
        $this->assertSame(AiSummaryStatus::Draft, $summary->status);
        $this->assertNotEmpty($summary->content['ringkasan']);
    }

    public function test_secret_meeting_requires_explicit_ai_consent(): void
    {
        $user = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $user->id,
            'confidentiality' => Confidentiality::Secret->value,
            'status' => MeetingStatus::Draft->value,
            'content_text' => 'Isi rahasia.',
        ]);

        $this->actingAs($user)
            ->post(route('meetings.ai.generate', $meeting))
            ->assertSessionHasErrors('ai_consent');

        $this->assertDatabaseCount('ai_summaries', 0);
    }

    public function test_summary_can_be_approved_and_action_items_applied(): void
    {
        $user = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $user->id,
            'confidentiality' => Confidentiality::Open->value,
            'status' => MeetingStatus::Draft->value,
        ]);

        $summary = $meeting->summaries()->create([
            'scope' => 'meeting',
            'content' => [
                'ringkasan' => 'Ringkasan uji.',
                'poin' => ['Poin uji.'],
                'keputusan' => [],
                'action_items' => [[
                    'title' => 'Tindak lanjut uji',
                    'description' => 'Detail.',
                    'pic_name' => null,
                    'division_name' => null,
                    'due_date' => now()->addDays(3)->toDateString(),
                ]],
            ],
            'version' => 1,
            'status' => AiSummaryStatus::Draft,
            'generation_status' => 'completed',
            'language' => 'id',
            'generated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('ai-summaries.approve', $summary))
            ->assertRedirect();

        $this->assertSame(AiSummaryStatus::Approved, $summary->fresh()->status);

        $this->actingAs($user)
            ->post(route('ai-summaries.apply', $summary))
            ->assertRedirect();

        $this->assertDatabaseHas('action_items', [
            'meeting_id' => $meeting->id,
            'title' => 'Tindak lanjut uji',
            'source' => 'ai',
        ]);
    }

    public function test_unrelated_member_cannot_generate_summary(): void
    {
        $creator = User::factory()->create(['role' => 'notulis']);
        $other = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $creator->id,
            'confidentiality' => Confidentiality::Restricted->value,
            'status' => MeetingStatus::Draft->value,
            'content_text' => 'Isi rapat.',
        ]);

        $this->actingAs($other)
            ->post(route('meetings.ai.generate', $meeting))
            ->assertForbidden();
    }
}
