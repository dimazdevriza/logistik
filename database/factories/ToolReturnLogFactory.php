<?php

namespace Database\Factories;

use App\Models\ToolReturnLog;
use App\Models\Tool;
use App\Models\House;
use App\Models\ToolUsage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ToolReturnLogFactory extends Factory
{
    protected $model = ToolReturnLog::class;

    public function definition(): array
    {
        return [
            'tool_id' => Tool::factory(),
            'house_id' => House::factory(),
            'tool_usage_id' => ToolUsage::factory(),
            'reported_by' => User::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'report_type' => fake()->randomElement(['normal', 'broken', 'lost']),
            'status' => fake()->randomElement(['pending', 'fixed', 'discarded']),
            'replacement_cost' => fake()->optional(0.5)->numberBetween(100000, 2000000),
            'notes' => fake()->optional(0.4)->sentence(),
        ];
    }
}
