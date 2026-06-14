<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryBudgetOverride;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryBudgetOverride>
 */
class CategoryBudgetOverrideFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'category_id' => Category::factory(),
            'period' => now()->startOfMonth()->toDateString(),
            'amount' => fake()->numberBetween(10000, 1000000),
        ];
    }
}
