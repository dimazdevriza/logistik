<?php

namespace Database\Factories;

use App\Models\ToolUsage;
use App\Models\House;
use App\Models\Tool;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ToolUsageFactory extends Factory
{
    protected $model = ToolUsage::class;

    public function definition(): array
    {
        return [
            'house_id' => House::factory(),
            'tool_id' => Tool::factory(),
            'user_id' => User::factory(),
            'quantity' => fake()->numberBetween(1, 5),
            'checkout_date' => fake()->dateTimeBetween('-2 months', 'now'),
            'return_date' => null,
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }

    public function returned(): static
    {
        return $this->state(fn (array $attrs) => [
            'return_date' => fake()->dateTimeBetween($attrs['checkout_date'], 'now'),
        ]);
    }
}
