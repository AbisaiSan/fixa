<?php

namespace Database\Factories;

use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'target_language' => 'en',
            'daily_goal_minutes' => Enrollment::DEFAULT_DAILY_GOAL,
            'timezone' => Enrollment::DEFAULT_TIMEZONE,
        ];
    }
}
