<?php

namespace Database\Factories;

use App\Models\StockIn;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockInFactory extends Factory
{
    protected $model = StockIn::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(10, 200);
        $unitPrice = fake()->numberBetween(5000, 200000);

        return [
            'material_id' => Material::factory(),
            'supplier_id' => Supplier::factory(),
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_cost' => $quantity * $unitPrice,
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
