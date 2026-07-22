<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tag> */
class TagFactory extends Factory
{
    private static array $used = [];

    public function definition(): array
    {
        $categories = ['Educación', 'Saludos', 'Comida', 'Lugares', 'Sentimientos', 'Verbos', 'Cultura', 'Formal', 'Casual'];

        do {
            $name = $this->faker->randomElement($categories) . '_' . $this->faker->unique()->numberBetween(1, 9999);
        } while (in_array($name, self::$used));
        self::$used[] = $name;

        return ['name' => $name];
    }
}
