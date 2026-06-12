<?php

namespace Database\Factories;

use App\Models\DayNote;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DayNote>
 */
class DayNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'date' => fake()->date(),
            'note' => fake()->sentence(),
        ];
    }
}
