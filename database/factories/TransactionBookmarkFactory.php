<?php

namespace Database\Factories;

use App\Models\TransactionBookmark;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransactionBookmark>
 */
class TransactionBookmarkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(2, true),
            'payload' => [
                'type' => 'expense',
                'description' => fake()->words(3, true),
            ],
            'position' => fake()->numberBetween(0, 100),
        ];
    }
}
