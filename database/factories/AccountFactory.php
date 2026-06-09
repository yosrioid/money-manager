<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'account_group_id' => null,
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(AccountType::cases()),
            'currency_code' => 'IDR',
            'opening_balance' => 0,
            'description' => null,
            'position' => fake()->numberBetween(0, 100),
            'is_visible' => true,
            'include_in_total' => true,
            'archived_at' => null,
        ];
    }

    public function inGroup(AccountGroup $group): static
    {
        return $this->state(['account_group_id' => $group->id]);
    }

    public function archived(): static
    {
        return $this->state(['archived_at' => now()]);
    }
}
