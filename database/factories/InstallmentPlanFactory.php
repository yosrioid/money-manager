<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\InstallmentPlan;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstallmentPlan>
 */
class InstallmentPlanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'transaction_id' => function (array $attributes): int {
                /** @var int $workspaceId */
                $workspaceId = $attributes['workspace_id'];

                return Transaction::query()->create([
                    'workspace_id' => $workspaceId,
                    'created_by' => User::factory()->create()->id,
                    'type' => TransactionType::Expense,
                    'status' => TransactionStatus::Posted,
                    'currency_code' => 'IDR',
                    'description' => 'Installment purchase',
                    'occurred_at' => now(),
                    'posted_at' => now(),
                ])->id;
            },
            'account_id' => Account::factory(),
            'total_amount' => fake()->numberBetween(100_000, 10_000_000),
            'installment_count' => fake()->numberBetween(2, 12),
            'first_due_date' => now()->addMonthNoOverflow()->startOfDay(),
        ];
    }
}
