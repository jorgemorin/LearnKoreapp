<?php

namespace Database\Factories;

use App\Models\Compound;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Compound> */
class CompoundFactory extends Factory
{
    public function definition(): array
    {
        $words = ['학교에서', '안녕하세요', '감사합니다', '한국어', '사랑해요', '먹어요', '공부해요', '한국'];
        static $used = [];

        do {
            $word = $this->faker->randomElement($words);
            $key  = $word . rand(1000, 9999);
        } while (in_array($key, $used));
        $used[] = $key;

        return [
            'full_text'   => $word . $key, // hacer único
            'translation' => $this->faker->sentence(3),
            'status'      => Compound::STATUS_PENDING,
        ];
    }

    public function withText(string $text): static
    {
        return $this->state(['full_text' => $text]);
    }

    public function approved(): static
    {
        return $this->state(['status' => Compound::STATUS_APPROVED]);
    }
}
