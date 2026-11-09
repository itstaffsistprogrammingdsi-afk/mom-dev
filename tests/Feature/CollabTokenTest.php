<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollabTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_receives_collab_token(): void
    {
        $notulis = User::factory()->create(['role' => 'notulis']);
        $meeting = Meeting::factory()->create([
            'created_by' => $notulis->id,
            'confidentiality' => Confidentiality::Open->value,
        ]);

        $this->actingAs($notulis)
            ->post(route('collab.token', $meeting))
            ->assertOk()
            ->assertJsonStructure(['url', 'document', 'token', 'user' => ['id', 'name']])
            ->assertJsonPath('document', "meeting-{$meeting->id}");
    }

    public function test_unrelated_user_cannot_get_collab_token(): void
    {
        $owner = User::factory()->create(['role' => 'notulis']);
        $outsider = User::factory()->create(['role' => 'member']);
        $meeting = Meeting::factory()->create([
            'created_by' => $owner->id,
            'confidentiality' => Confidentiality::Restricted->value,
        ]);

        $this->actingAs($outsider)
            ->post(route('collab.token', $meeting))
            ->assertForbidden();
    }
}
