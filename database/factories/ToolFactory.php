<?php

namespace Database\Factories;

use App\Models\Tool;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ToolFactory extends Factory
{
    protected $model = Tool::class;

    protected static int $codeCounter = 0;

    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 20);

        return [
            'category_id' => Category::factory()->tool(),
            'name' => fake()->words(2, true),
            'code' => 'TL-' . str_pad(++self::$codeCounter, 4, '0', STR_PAD_LEFT),
            'condition' => 'baik',
            'purchase_price' => fake()->numberBetween(100000, 5000000),
            'total_qty' => $qty,
            'available_qty' => $qty,
        ];
    }

    public function damaged(): static
    {
        return $this->state(fn () => ['condition' => 'rusak']);
    }

    public function withAvailable(int $available): static
    {
        return $this->state(fn (array $attrs) => [
            'available_qty' => min($available, $attrs['total_qty']),
        ]);
    }
}
