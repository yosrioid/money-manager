<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'parent_id' => null,
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(CategoryType::cases()),
            'color' => null,
            'icon' => null,
            'position' => fake()->numberBetween(0, 100),
            'is_visible' => true,
            'archived_at' => null,
        ];
    }

    public function income(): static
    {
        return $this->state(['type' => CategoryType::Income]);
    }

    public function expense(): static
    {
        return $this->state(['type' => CategoryType::Expense]);
    }

    public function subcategoryOf(Category $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'workspace_id' => $parent->workspace_id,
            'type' => $parent->type,
        ]);
    }

    public function archived(): static
    {
        return $this->state(['archived_at' => now()]);
    }
}
