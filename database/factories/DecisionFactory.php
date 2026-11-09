<?php

namespace Database\Factories;

use App\Models\Decision;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Decision>
 */
class DecisionFactory extends Factory
{
    protected $model = Decision::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'content' => fake()->sentence(12),
            'decided_at' => fake()->dateTimeBetween('-2 months'),
        ];
    }
}
