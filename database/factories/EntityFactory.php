<?php

namespace Database\Factories;

use App\Models\Entity;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Entity> */
class EntityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'text'    => $this->faker->unique()->lexify('??'),
            'type'    => $this->faker->randomElement(['root', 'particle', 'word']),
            'meaning' => $this->faker->word(),
            'status'  => Entity::STATUS_PENDING,
        ];
    }
}
