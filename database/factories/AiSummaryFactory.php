<?php

namespace Database\Factories;

use App\Models\AiSummary;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiSummary>
 */
class AiSummaryFactory extends Factory
{
    protected $model = AiSummary::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'scope' => 'meeting',
            'language' => 'id',
            'content' => [
                'ringkasan' => fake()->paragraph(),
                'poin' => [fake()->sentence(), fake()->sentence()],
                'keputusan' => [fake()->sentence()],
            ],
            'model' => 'gpt-4o-mini',
            'version' => 1,
            'status' => 'draft',
        ];
    }
}
