<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->words(2, true),
            'default_currency' => 'IDR',
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'number_format' => 'id-ID',
            'first_day_of_week' => 1,
            'month_start_day' => 1,
            'adjust_month_for_weekend' => false,
            'application_lock_minutes' => 0,
        ];
    }
}
