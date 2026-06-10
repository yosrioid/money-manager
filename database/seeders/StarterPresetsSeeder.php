<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class StarterPresetsSeeder extends Seeder
{
    /**
     * Seed starter account groups, accounts, income categories, and expense categories
     * for the given workspace. Each item is idempotent — it will not duplicate if run twice.
     */
    public function run(Workspace $workspace): void
    {
        $this->seedAccountGroups($workspace);
        $this->seedCategories($workspace);
    }

    private function seedAccountGroups(Workspace $workspace): void
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
                        'opening_balance' => 0,
                        'position' => $accountPosition,
                    ],
                );
            }
        }
    }

    private function seedCategories(Workspace $workspace): void
    {
        $incomeCategories = [
            'Salary',
            'Business Income',
            'Freelance',
            'Investment Return',
            'Other Income',
        ];

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

        foreach ($incomeCategories as $position => $name) {
            Category::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $name, 'type' => CategoryType::Income, 'parent_id' => null],
                ['position' => $position],
            );
        }

        foreach (array_keys($expenseCategories) as $position => $parentName) {
            $children = $expenseCategories[$parentName];

            $parent = Category::query()->firstOrCreate(
                ['workspace_id' => $workspace->id, 'name' => $parentName, 'type' => CategoryType::Expense, 'parent_id' => null],
                ['position' => $position],
            );

            foreach ($children as $childPosition => $childName) {
                Category::query()->firstOrCreate(
                    ['workspace_id' => $workspace->id, 'name' => $childName, 'type' => CategoryType::Expense, 'parent_id' => $parent->id],
                    ['position' => $childPosition],
                );
            }
        }
    }
}
