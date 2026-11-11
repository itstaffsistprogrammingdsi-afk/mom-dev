<?php

namespace Tests\Feature;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_and_manage_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $division = Division::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Pengguna Baru',
                'email' => 'baru@mom.test',
                'password' => 'password123',
                'role' => 'member',
                'division_id' => $division->id,
                'job_title' => 'Staf',
                'is_active' => true,
            ])
            ->assertRedirect();

        $created = User::query()->where('email', 'baru@mom.test')->firstOrFail();
        $this->assertNotNull($created->email_verified_at);

        $this->actingAs($admin)
            ->post(route('admin.templates.store'), [
                'name' => 'Template Uji',
                'document_type' => 'meeting',
                'meeting_type' => 'weekly',
                'description' => 'Deskripsi template uji.',
                'is_active' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_templates', ['name' => 'Template Uji']);
    }

    public function test_division_and_people_pages_render_for_authorized_user(): void
    {
        $division = Division::factory()->create(['name' => 'Divisi Uji', 'slug' => 'divisi-uji']);
        $member = User::factory()->create(['role' => 'member', 'division_id' => $division->id]);
        $admin = User::factory()->create(['role' => 'admin']);

        $meeting = Meeting::factory()->create([
            'division_id' => $division->id,
            'created_by' => $admin->id,
            'status' => MeetingStatus::Draft->value,
            'confidentiality' => Confidentiality::Open->value,
        ]);
        $meeting->participants()->create(['user_id' => $member->id, 'name' => $member->name]);

        $this->actingAs($admin)
            ->get(route('divisions.show', $division))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('people.show', $member))
            ->assertOk();
    }
}
