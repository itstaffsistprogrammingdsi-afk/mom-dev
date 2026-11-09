<?php

namespace Database\Factories;

use App\Enums\Confidentiality;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Division;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        $content = [
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Pembahasan']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => fake()->paragraph()]]],
            ],
        ];

        return [
            'title' => fake()->sentence(5),
            'meeting_type' => fake()->randomElement(MeetingType::cases()),
            'division_id' => Division::factory(),
            'created_by' => User::factory(),
            'scheduled_at' => fake()->dateTimeBetween('-2 months', '+1 month'),
            'location' => fake()->randomElement(['Ruang Rapat A', 'Ruang Rapat B', 'Daring']),
            'agenda' => fake()->sentence(),
            'content' => $content,
            'content_text' => Meeting::contentToText($content),
            'status' => fake()->randomElement([MeetingStatus::Draft, MeetingStatus::Final]),
            'confidentiality' => fake()->randomElement(Confidentiality::cases()),
        ];
    }
}
