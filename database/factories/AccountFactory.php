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
            'type' => fake()->randomElement(array_filter(
                AccountType::cases(),
                fn (AccountType $type): bool => ! in_array($type, [AccountType::CreditCard, AccountType::DebitCard], true),
            )),
            'currency_code' => 'IDR',
            'credit_limit' => null,
            'statement_closing_day' => null,
            'payment_due_day' => null,
            'linked_account_id' => null,
            'description' => null,
            'position' => fake()->numberBetween(0, 100),
            'is_visible' => true,
            'is_favorite' => false,
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

    public function favorite(): static
    {
        return $this->state(['is_favorite' => true]);
    }

    public function creditCard(int $creditLimit = 0, int $statementClosingDay = 25, int $paymentDueDay = 10): static
    {
        return $this->state([
            'type' => AccountType::CreditCard,
            'credit_limit' => $creditLimit,
            'statement_closing_day' => $statementClosingDay,
            'payment_due_day' => $paymentDueDay,
        ]);
    }

    public function debitCard(Account $linkedAccount): static
    {
        return $this->state([
            'type' => AccountType::DebitCard,
            'currency_code' => $linkedAccount->currency_code,
            'linked_account_id' => $linkedAccount->id,
        ]);
    }
}
