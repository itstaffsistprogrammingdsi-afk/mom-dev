<?php

namespace Database\Factories;

use App\Enums\AttendanceStatus;
use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingParticipant>
 */
class MeetingParticipantFactory extends Factory
{
    protected $model = MeetingParticipant::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'attendance' => fake()->randomElement(AttendanceStatus::cases()),
            'is_external' => false,
        ];
    }
}
