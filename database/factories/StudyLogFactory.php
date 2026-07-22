<?php

namespace Database\Factories;

use App\Models\Compound;
use App\Models\StudyLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StudyLog> */
class StudyLogFactory extends Factory
{
    public function definition(): array
    {
        $rating     = $this->faker->randomElement(['again', 'again', 'hard', 'good', 'good', 'good', 'good', 'easy']);
        $isCorrect  = StudyLog::isCorrectFromRating($rating);

        return [
            'user_id'       => User::factory(),
            'item_id'       => Compound::factory(),
            'item_type'     => 'compound',
            'is_correct'    => $isCorrect,
            'rating'        => $rating,
            'time_taken_ms' => $this->faker->numberBetween(500, 8000),
        ];
    }

    public function correct(): static
    {
        return $this->state(['is_correct' => true, 'rating' => 'good']);
    }

    public function incorrect(): static
    {
        return $this->state(['is_correct' => false, 'rating' => 'again']);
    }
}
