<?php

namespace Database\Factories;

use App\Models\Compound;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserProgress> */
class UserProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'             => User::factory(),
            'item_id'             => Compound::factory(),
            'item_type'           => 'compound',
            'next_review_date'    => now()->toDateString(),
            'ease_factor'         => 2.5,
            'interval_days'       => 0,
            'repetitions'         => 0,
            'card_state'          => UserProgress::STATE_NEW,
            'lapses'              => 0,
            'learning_step_index' => 0,
        ];
    }

    public function dueToday(): static
    {
        return $this->state(['next_review_date' => now()->toDateString()]);
    }

    public function dueFuture(int $days = 3): static
    {
        return $this->state(['next_review_date' => now()->addDays($days)->toDateString()]);
    }

    public function asYoung(): static
    {
        return $this->state([
            'card_state'    => UserProgress::STATE_YOUNG,
            'interval_days' => 6,
            'repetitions'   => 2,
        ]);
    }

    public function asMature(): static
    {
        return $this->state([
            'card_state'    => UserProgress::STATE_MATURE,
            'interval_days' => 30,
            'repetitions'   => 5,
        ]);
    }
}
