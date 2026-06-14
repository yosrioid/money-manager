<?php

use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Transactions\RecordTransfer;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\User;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\IOFactory;

function setUpReportWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create();

    return [$user, $workspace, $account];
}

test('reports page summarizes income, expenses, category, and merchant activity for the current month', function () {
    [$user, $workspace, $account] = setUpReportWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);
    $merchant = Merchant::factory()->for($workspace)->create(['name' => 'Supermarket']);

    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 150000, 'Groceries', now(), $user, merchant: $merchant);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 4500000, 'Salary', now(), $user);

    $this->actingAs($user)->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('reports/Index')
            ->where('month', '2026-06-01')
            ->loadDeferredProps(['reports', 'net-worth'], fn (Assert $page) => $page
                ->where('summary.income', ['IDR' => 4500000])
                ->where('summary.expense', ['IDR' => 150000])
                ->where('summary.net', ['IDR' => 4350000])
                ->where('summary.count', 2)
                ->has('categoryBreakdown.expense', 1)
                ->where('categoryBreakdown.expense.0.name', 'Groceries')
                ->has('categoryBreakdown.income', 1)
                ->has('merchantBreakdown', 1)
                ->where('merchantBreakdown.0.name', 'Supermarket')
                ->where('merchantBreakdown.0.amount', 150000)
            )
        );
});

test('reports page respects account, category, merchant, and tag filters', function () {
    [$user, $workspace, $account] = setUpReportWorkspace();
    $otherAccount = Account::factory()->for($workspace)->create();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $groceries = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);
    $fuel = Category::factory()->for($workspace)->expense()->create(['name' => 'Fuel']);

    app(RecordIncomeExpense::class)->record($account, $groceries, TransactionType::Expense, 100000, 'Groceries', now(), $user);
    app(RecordIncomeExpense::class)->record($otherAccount, $fuel, TransactionType::Expense, 75000, 'Fuel', now(), $user);

    $this->actingAs($user)->get(route('reports.index', ['category_ids' => [$groceries->id]]))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(['reports', 'net-worth'], fn (Assert $page) => $page
                ->where('summary.expense', ['IDR' => 100000])
                ->has('categoryBreakdown.expense', 1)
                ->where('categoryBreakdown.expense.0.name', 'Groceries')
            )
        );

    $this->actingAs($user)->get(route('reports.index', ['account_ids' => [$otherAccount->id]]))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(['reports', 'net-worth'], fn (Assert $page) => $page
                ->where('summary.expense', ['IDR' => 75000])
            )
        );
});

test('reports page only includes the workspace configured report widgets', function () {
    [$user, $workspace, $account] = setUpReportWorkspace();

    $workspace->update(['report_widgets' => ['summary', 'netWorth']]);

    $this->actingAs($user)->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('visibleWidgets', ['summary', 'netWorth'])
            ->missing('comparison')
            ->missing('categoryBreakdown')
            ->missing('merchantBreakdown')
            ->missing('accountActivity')
            ->missing('netWorthTrend')
            ->loadDeferredProps(['reports', 'net-worth'], fn (Assert $page) => $page
                ->has('summary')
                ->has('netWorth')
            )
        );
});

test('the report excel export includes summary, category, and net worth sheets', function () {
    [$user, $workspace, $account] = setUpReportWorkspace();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);

    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 150000, 'Groceries', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 4500000, 'Salary', now(), $user);

    $response = $this->actingAs($user)->get(route('reports.export', ['month' => '2026-06']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $response->streamedContent());

    $spreadsheet = IOFactory::load($path);
    unlink($path);

    $sheetNames = $spreadsheet->getSheetNames();
    expect($sheetNames)->toBe(['Summary', 'Categories', 'Merchants', 'Accounts', 'Net worth']);

    $summary = $spreadsheet->getSheetByName('Summary');
    expect($summary->getCell('A1')->getValue())->toBe('Metric');
    expect($summary->getCell('B2')->getValue())->toBe('4500000 IDR');
    expect($summary->getCell('B3')->getValue())->toBe('150000 IDR');

    $categories = $spreadsheet->getSheetByName('Categories');
    expect($categories->getCell('B2')->getValue())->toBe('Groceries');
});

test('the annual transactions excel export includes a monthly summary sheet', function () {
    [$user, $workspace, $account] = setUpReportWorkspace();

    $incomeCategory = Category::factory()->for($workspace)->income()->create();

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 4500000, 'Salary', now(), $user);

    $response = $this->actingAs($user)->get(route('transactions.monthly.export', ['year' => '2026']));

    $response->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($path, $response->streamedContent());

    $spreadsheet = IOFactory::load($path);
    unlink($path);

    $sheet = $spreadsheet->getSheetByName('Monthly summary');
    expect($sheet->getCell('A1')->getValue())->toBe('Month');
    expect($sheet->getCell('A2')->getValue())->toBe('2026-06');
    expect($sheet->getCell('B2')->getValue())->toBe('4500000 IDR');
});

test('reports page shows asset, liability, and net worth summaries', function () {
    [$user, $workspace, $account] = setUpReportWorkspace();
    $account->update(['type' => AccountType::Cash]);
    $creditCard = Account::factory()->for($workspace)->create(['type' => AccountType::CreditCard]);

    Carbon::setTestNow(now()->setDate(2026, 6, 10)->setTime(12, 0));

    $expenseCategory = Category::factory()->for($workspace)->expense()->create();

    app(RecordTransfer::class)->record($account, $creditCard, 200000, 0, null, 'Opening balance transfer', now(), $user);
    app(RecordIncomeExpense::class)->record($creditCard, $expenseCategory, TransactionType::Expense, 50000, 'Card spend', now(), $user);

    $this->actingAs($user)->get(route('reports.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps(['reports', 'net-worth'], fn (Assert $page) => $page
                ->where('netWorth.assets', ['IDR' => -200000])
                ->where('netWorth.liabilities', ['IDR' => 150000])
                ->where('netWorth.net', ['IDR' => -50000])
                ->has('netWorthTrend', 6)
            )
        );
});
