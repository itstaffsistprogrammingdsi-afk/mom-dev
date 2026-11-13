<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\QuickNote;
use App\Models\User;
use App\Notifications\QuickNoteMentionNotification;
use App\Notifications\QuickNoteResponseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QuickNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_quick_note_and_tag_recipient_with_response_hours(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => 'member']);
        $recipient = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($creator)->post(route('quick-notes.store'), [
            'title' => 'Ide cepat',
            'content' => $this->contentMentioning($recipient, 'UI UX sistem KoisK MCD bagus nih'),
            'mentions' => [
                ['user_id' => $recipient->id, 'response_hours' => 4],
            ],
        ]);

        $note = QuickNote::query()->firstOrFail();

        $response->assertRedirect(route('quick-notes.show', $note));

        $this->assertSame($creator->id, $note->user_id);
        $this->assertDatabaseHas('quick_note_mentions', [
            'quick_note_id' => $note->id,
            'user_id' => $recipient->id,
            'response_hours' => 4,
        ]);

        Notification::assertSentTo($recipient, QuickNoteMentionNotification::class);
    }

    public function test_only_creator_mentioned_user_and_admin_can_view_quick_note(): void
    {
        $creator = User::factory()->create(['role' => 'member']);
        $mentioned = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);
        $admin = User::factory()->create(['role' => 'admin']);

        $note = $this->makeNote($creator);
        $note->mentions()->create([
            'user_id' => $mentioned->id,
            'response_hours' => 24,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($creator)->get(route('quick-notes.show', $note))->assertOk();
        $this->actingAs($mentioned)->get(route('quick-notes.show', $note))->assertOk();
        $this->actingAs($admin)->get(route('quick-notes.show', $note))->assertOk();
        $this->actingAs($stranger)->get(route('quick-notes.show', $note))->assertForbidden();
    }

    public function test_mentioned_user_can_respond_and_creator_is_notified(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => 'member']);
        $mentioned = User::factory()->create(['role' => 'member']);

        $note = $this->makeNote($creator);
        $mention = $note->mentions()->create([
            'user_id' => $mentioned->id,
            'response_hours' => 4,
            'due_at' => now()->addHours(4),
        ]);

        $this->actingAs($mentioned)
            ->post(route('quick-notes.respond', $note), [
                'response_status' => 'acknowledged',
                'response_body' => 'Sudah saya baca, akan saya masukkan ke agenda.',
            ])
            ->assertRedirect();

        $mention->refresh();

        $this->assertNotNull($mention->responded_at);
        $this->assertSame('acknowledged', $mention->response_status->value);
        $this->assertSame('Sudah saya baca, akan saya masukkan ke agenda.', $mention->response_body);

        Notification::assertSentTo($creator, QuickNoteResponseNotification::class);
    }

    public function test_unrelated_user_cannot_respond_to_quick_note(): void
    {
        $creator = User::factory()->create(['role' => 'member']);
        $mentioned = User::factory()->create(['role' => 'member']);
        $stranger = User::factory()->create(['role' => 'member']);

        $note = $this->makeNote($creator);
        $note->mentions()->create([
            'user_id' => $mentioned->id,
            'response_hours' => 24,
            'due_at' => now()->addDay(),
        ]);

        $this->actingAs($stranger)
            ->post(route('quick-notes.respond', $note), [
                'response_status' => 'acknowledged',
                'response_body' => 'Mencoba merespons.',
            ])
            ->assertForbidden();
    }


    public function test_quick_notes_can_be_filtered_by_division(): void
    {
        $creator = User::factory()->create(['role' => 'member']);
        $it = Division::factory()->create(['name' => 'Teknologi Informasi']);
        $finance = Division::factory()->create(['name' => 'Keuangan']);

        $itNote = $this->makeNote($creator);
        $itNote->update(['division_id' => $it->id]);

        $financeNote = $this->makeNote($creator);
        $financeNote->update(['division_id' => $finance->id]);

        $this->actingAs($creator)
            ->get(route('quick-notes.index', ['division' => $it->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('QuickNotes/Index')
                ->has('notes.data', 1)
                ->where('notes.data.0.id', $itNote->id)
                ->where('filters.division', (string) $it->id)
            );
    }

    public function test_photo_can_be_uploaded_viewed_and_removed(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create(['role' => 'member']);

        $this->actingAs($creator)
            ->post(route('quick-notes.store'), [
                'title' => 'Ide dengan foto',
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        ['type' => 'image', 'attrs' => ['src' => 'blob:http://localhost/preview']],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Keterangan foto']]],
                    ],
                ],
                'photos' => [UploadedFile::fake()->image('ide.jpg', 800, 600)],
            ])
            ->assertRedirect();

        $note = QuickNote::query()->firstOrFail();
        $path = $note->photo_path;
        $photoId = array_key_first($note->photos);

        $this->assertNotNull($path);
        $this->assertNotNull($photoId);
        Storage::disk('public')->assertExists($path);
        $this->assertSame(
            route('quick-notes.photo-item', [$note, $photoId]),
            data_get($note->fresh()->content, 'content.0.attrs.src'),
        );
        $this->assertSame($photoId, data_get($note->fresh()->content, 'content.0.attrs.photoId'));

        $this->actingAs($creator)
            ->get(route('quick-notes.photo-item', [$note, $photoId]))
            ->assertOk();

        $this->actingAs($creator)
            ->get(route('quick-notes.photo', $note))
            ->assertOk();

        $this->actingAs($creator)
            ->patch(route('quick-notes.update', $note), [
                'title' => $note->title,
                'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            ])
            ->assertRedirect();

        $this->assertNull($note->fresh()->photo_path);
        $this->assertNull($note->fresh()->photos);
        Storage::disk('public')->assertMissing($path);
        $this->assertStringNotContainsString('"image"', json_encode($note->fresh()->content));
    }

    public function test_multiple_photos_can_be_attached_to_one_quick_note(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create(['role' => 'member']);

        $this->actingAs($creator)
            ->post(route('quick-notes.store'), [
                'title' => 'Beberapa foto',
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        ['type' => 'image', 'attrs' => ['src' => 'blob:http://localhost/first']],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Foto pertama']]],
                        ['type' => 'image', 'attrs' => ['src' => 'blob:http://localhost/second']],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Foto kedua']]],
                    ],
                ],
                'photos' => [
                    UploadedFile::fake()->image('pertama.jpg'),
                    UploadedFile::fake()->image('kedua.jpg'),
                ],
            ])
            ->assertRedirect();

        $note = QuickNote::query()->firstOrFail();
        $photos = $note->photos;

        $this->assertNotNull($photos);
        $this->assertCount(2, $photos);

        $photoIds = array_keys($photos);
        $firstId = $photoIds[0];
        $secondId = $photoIds[1];

        Storage::disk('public')->assertExists($photos[$firstId]['path']);
        Storage::disk('public')->assertExists($photos[$secondId]['path']);

        $content = $note->content;

        $this->assertSame(route('quick-notes.photo-item', [$note, $firstId]), data_get($content, 'content.0.attrs.src'));
        $this->assertSame(route('quick-notes.photo-item', [$note, $secondId]), data_get($content, 'content.2.attrs.src'));
        $this->assertSame($photos[$firstId]['path'], $note->photo_path);

        $this->actingAs($creator)
            ->get(route('quick-notes.photo-item', [$note, $firstId]))
            ->assertOk();

        $this->actingAs($creator)
            ->get(route('quick-notes.photo-item', [$note, $secondId]))
            ->assertOk();
    }

    public function test_removing_one_image_prunes_only_that_photo(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create(['role' => 'member']);

        $this->actingAs($creator)
            ->post(route('quick-notes.store'), [
                'title' => 'Dua foto',
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        ['type' => 'image', 'attrs' => ['src' => 'blob:http://localhost/first']],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Satu']]],
                        ['type' => 'image', 'attrs' => ['src' => 'blob:http://localhost/second']],
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Dua']]],
                    ],
                ],
                'photos' => [
                    UploadedFile::fake()->image('satu.jpg'),
                    UploadedFile::fake()->image('dua.jpg'),
                ],
            ])
            ->assertRedirect();

        $note = QuickNote::query()->firstOrFail();
        $photos = $note->photos;
        $ids = array_keys($photos);
        $firstId = $ids[0];
        $secondId = $ids[1];
        $secondPath = $photos[$secondId]['path'];
        $firstImage = $note->content['content'][0];

        $this->actingAs($creator)
            ->patch(route('quick-notes.update', $note), [
                'title' => $note->title,
                'content' => [
                    'type' => 'doc',
                    'content' => [
                        $firstImage,
                        ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Satu']]],
                    ],
                ],
            ])
            ->assertRedirect();

        $note->refresh();

        $this->assertCount(1, $note->photos);
        $this->assertArrayHasKey($firstId, $note->photos);
        $this->assertArrayNotHasKey($secondId, $note->photos);
        Storage::disk('public')->assertExists($photos[$firstId]['path']);
        Storage::disk('public')->assertMissing($secondPath);
    }

    public function test_deleting_quick_note_removes_its_photo(): void
    {
        Storage::fake('public');

        $creator = User::factory()->create(['role' => 'member']);
        $note = $this->makeNote($creator);

        $path = UploadedFile::fake()->image('hapus.jpg')->store('quick-notes', 'public');
        $note->update([
            'photo_path' => $path,
            'photos' => [
                (string) Str::uuid() => ['path' => $path, 'original_name' => 'hapus.jpg'],
            ],
        ]);

        Storage::disk('public')->assertExists($path);

        $this->actingAs($creator)
            ->delete(route('quick-notes.destroy', $note))
            ->assertRedirect(route('quick-notes.index'));

        Storage::disk('public')->assertMissing($path);
    }

    private function makeNote(User $creator): QuickNote
    {
        return QuickNote::create([
            'user_id' => $creator->id,
            'title' => 'Catatan cepat',
            'content' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
            'content_text' => 'Catatan cepat',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contentMentioning(User $user, string $text): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text.' '],
                        [
                            'type' => 'mention',
                            'attrs' => [
                                'id' => $user->id,
                                'label' => $user->name,
                                'type' => 'person',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
