<?php

namespace App\Domain\Workspaces;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class ApplyStarterPresets
{
    public function apply(Workspace $workspace): void
    {
        DB::transaction(function () use ($workspace): void {
            $this->applyAccountPresets($workspace);
            $this->applyCategoryPresets($workspace);
        });
    }

    private function applyAccountPresets(Workspace $workspace): void
    {
        $groups = [
            ['name' => 'Cash', 'accounts' => [
                ['name' => 'Wallet', 'type' => AccountType::Cash],
            ]],
            ['name' => 'Bank Accounts', 'accounts' => [
                ['name' => 'Main Account', 'type' => AccountType::BankAccount],
            ]],
            ['name' => 'E-Wallets', 'accounts' => [
                ['name' => 'GoPay', 'type' => AccountType::EWallet],
                ['name' => 'OVO', 'type' => AccountType::EWallet],
            ]],
        ];

        foreach ($groups as $position => $groupData) {
            $group = AccountGroup::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $groupData['name']],
                ['position' => $position],
            );

            foreach ($groupData['accounts'] as $accountPosition => $accountData) {
                Account::query()->firstOrCreate(
                    ['workspace_id' => $workspace->id, 'name' => $accountData['name']],
                    [
                        'account_group_id' => $group->id,
                        'type' => $accountData['type'],
                        'currency_code' => $workspace->default_currency,
                        'position' => $accountPosition,
                    ],
                );
            }
        }
    }

    private function applyCategoryPresets(Workspace $workspace): void
    {
        foreach (['Salary', 'Business Income', 'Freelance', 'Investment Return', 'Other Income'] as $position => $name) {
            Category::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $name, 'type' => CategoryType::Income, 'parent_id' => null],
                ['position' => $position],
            );
        }

        $expenseCategories = [
            'Food & Drinks' => ['Groceries', 'Restaurant', 'Coffee'],
            'Transportation' => ['Fuel', 'Public Transport', 'Parking'],
            'Housing' => ['Rent', 'Electricity', 'Water', 'Internet'],
            'Health' => ['Medicine', 'Doctor', 'Gym'],
            'Shopping' => ['Clothes', 'Electronics', 'Household'],
            'Entertainment' => ['Movies', 'Games', 'Subscriptions'],
            'Education' => ['Tuition', 'Books', 'Courses'],
            'Personal Care' => ['Haircut', 'Beauty'],
            'Other Expenses' => [],
        ];

        foreach (array_keys($expenseCategories) as $position => $parentName) {
            $parent = Category::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $parentName, 'type' => CategoryType::Expense, 'parent_id' => null],
                ['position' => $position],
            );

            foreach ($expenseCategories[$parentName] as $childPosition => $childName) {
                Category::query()->firstOrCreate(
                    ['workspace_id' => $workspace->id, 'name' => $childName, 'type' => CategoryType::Expense, 'parent_id' => $parent->id],
                    ['position' => $childPosition],
                );
            }
        }
    }
}
