<?php

namespace Database\Factories;

use App\Models\MaterialUsage;
use App\Models\House;
use App\Models\Material;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterialUsageFactory extends Factory
{
    protected $model = MaterialUsage::class;

    public function definition(): array
    {
        $quantity = fake()->randomFloat(2, 1, 100);
        $unitPrice = fake()->numberBetween(5000, 200000);

        return [
            'house_id' => House::factory(),
            'material_id' => Material::factory(),
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'unit_price_at_usage' => $unitPrice,
            'total_cost' => round($quantity * $unitPrice, 2),
            'usage_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
