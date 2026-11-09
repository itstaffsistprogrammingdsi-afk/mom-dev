<?php

namespace Database\Factories;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActionItem>
 */
class ActionItemFactory extends Factory
{
    protected $model = ActionItem::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'title' => fake()->sentence(6),
            'description' => fake()->sentence(),
            'due_date' => fake()->dateTimeBetween('-1 week', '+3 weeks'),
            'status' => fake()->randomElement(ActionItemStatus::cases()),
            'source' => 'manual',
        ];
    }
}
