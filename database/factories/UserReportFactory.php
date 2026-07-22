<?php

namespace Database\Factories;

use App\Models\Compound;
use App\Models\User;
use App\Models\UserReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserReport> */
class UserReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'category'          => $this->faker->randomElement(array_keys(UserReport::$categories)),
            'description'       => $this->faker->sentence(12),
            'related_item_id'   => null,
            'related_item_type' => null,
            'status'            => UserReport::STATUS_PENDING,
            'admin_notes'       => null,
            'reviewed_by'       => null,
            'reviewed_at'       => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => UserReport::STATUS_PENDING]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status'      => UserReport::STATUS_RESOLVED,
            'admin_notes' => $this->faker->sentence(8),
            'reviewed_by' => User::factory()->create(['role' => 'admin'])->id,
            'reviewed_at' => now(),
        ]);
    }

    public function withCompound(): static
    {
        $compound = Compound::factory()->create();
        return $this->state([
            'related_item_id'   => $compound->id,
            'related_item_type' => 'compound',
        ]);
    }
}
