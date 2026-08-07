<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Supplier;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialFactory extends Factory
{
    protected $model = Material::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'category_id' => Category::factory()->material(),
            'name' => fake()->words(3, true),
            'unit' => fake()->randomElement(['sak', 'batang', 'buah', 'lembar', 'kg', 'meter', 'm²', 'm³', 'liter', 'kaleng', 'dus']),
            'unit_price' => fake()->numberBetween(5000, 500000),
            'stock' => fake()->numberBetween(10, 500),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn () => ['stock' => fake()->numberBetween(1, 10)]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
