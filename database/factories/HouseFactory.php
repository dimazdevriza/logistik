<?php

namespace Database\Factories;

use App\Models\House;
use Illuminate\Database\Eloquent\Factories\Factory;

class HouseFactory extends Factory
{
    protected $model = House::class;

    protected static int $codeCounter = 0;

    public function definition(): array
    {
        $blok = strtoupper(fake()->randomLetter());
        $num = str_pad(fake()->numberBetween(1, 50), 2, '0', STR_PAD_LEFT);

        return [
            'house_code' => date('Y') . '-' . $blok . $num . (++self::$codeCounter),
            'name' => 'Blok ' . $blok . '-' . $num,
            'type' => fake()->randomElement(['Tipe 36', 'Tipe 45', 'Tipe 60', 'Tipe 70']),
            'status' => 'pembangunan',
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'target_end_date' => fake()->dateTimeBetween('now', '+12 months'),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'selesai']);
    }

    public function planning(): static
    {
        return $this->state(fn () => ['status' => 'perencanaan']);
    }
}
