<?php

use App\Domain\Export\GenerateTransactionsCsv;
use App\Domain\Transactions\FilterTransactionsQuery;
use App\Domain\Transactions\RecordIncomeExpense;
use App\Domain\Workspaces\CreatePersonalWorkspace;
use App\Enums\TransactionExportStatus;
use App\Enums\TransactionType;
use App\Jobs\GenerateTransactionsExportFile;
use App\Models\Account;
use App\Models\Category;
use App\Models\TransactionExport;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function setUpTransactionExportQueueWorkspace(): array
{
    $user = User::factory()->create();
    $workspace = app(CreatePersonalWorkspace::class)->create($user);
    $account = Account::factory()->for($workspace)->create(['name' => 'Cash Wallet']);
    $incomeCategory = Category::factory()->for($workspace)->income()->create(['name' => 'Salary']);
    $expenseCategory = Category::factory()->for($workspace)->expense()->create(['name' => 'Groceries']);

    return [$user, $workspace, $account, $incomeCategory, $expenseCategory];
}

test('guests cannot access queued exports', function () {
    $this->get(route('transactions.exports.index'))->assertRedirect(route('login'));
    $this->post(route('transactions.exports.store'))->assertRedirect(route('login'));
});

test('queuing an export generates a downloadable csv file', function () {
    Storage::fake('local');

    [$user, $workspace, $account, $incomeCategory, $expenseCategory] = setUpTransactionExportQueueWorkspace();

    app(RecordIncomeExpense::class)->record($account, $incomeCategory, TransactionType::Income, 50000, 'Salary', now(), $user);
    app(RecordIncomeExpense::class)->record($account, $expenseCategory, TransactionType::Expense, 15000, 'Groceries', now(), $user);

    $this->actingAs($user)
        ->post(route('transactions.exports.store'), ['type' => 'expense'])
        ->assertRedirect(route('transactions.exports.index'));

    $export = TransactionExport::query()->where('workspace_id', $workspace->id)->firstOrFail();

    expect($export->getRawOriginal('status'))->toBe(TransactionExportStatus::Ready->value);
    expect($export->file_path)->not->toBeNull();
    expect($export->filters)->toMatchArray(['type' => 'expense']);

    Storage::disk('local')->assertExists($export->file_path);
    expect(Storage::disk('local')->get($export->file_path))->toContain('Groceries')
        ->and(Storage::disk('local')->get($export->file_path))->not->toContain('Salary');

    $this->actingAs($user)
        ->get(route('transactions.exports.index'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('transactions.exports.download', $export))
        ->assertOk()
        ->assertHeader('Content-Disposition', "attachment; filename=transactions-export-{$export->id}.csv");
});

test('a download is only available once the export is ready', function () {
    [$user, $workspace] = setUpTransactionExportQueueWorkspace();

    $export = $workspace->transactionExports()->create([
        'created_by' => $user->id,
        'status' => TransactionExportStatus::Pending,
        'filters' => [],
    ]);

    $this->actingAs($user)
        ->get(route('transactions.exports.download', $export))
        ->assertNotFound();
});

test('the export job re-authorizes the creator before generating the file', function () {
    Storage::fake('local');

    [$user, $workspace] = setUpTransactionExportQueueWorkspace();

    $export = $workspace->transactionExports()->create([
        'created_by' => $user->id,
        'status' => TransactionExportStatus::Pending,
        'filters' => [],
    ]);

    $workspace->memberships()->where('user_id', $user->id)->delete();

    app(GenerateTransactionsExportFile::class, ['transactionExportId' => $export->id])->handle(
        app(FilterTransactionsQuery::class),
        app(GenerateTransactionsCsv::class),
    );

    expect($export->refresh()->getRawOriginal('status'))->toBe(TransactionExportStatus::Failed->value);
    expect($export->file_path)->toBeNull();
});

test('exports from another workspace cannot be downloaded', function () {
    Storage::fake('local');

    [$user] = setUpTransactionExportQueueWorkspace();
    [$otherUser, $otherWorkspace] = setUpTransactionExportQueueWorkspace();

    $export = $otherWorkspace->transactionExports()->create([
        'created_by' => $otherUser->id,
        'status' => TransactionExportStatus::Ready,
        'filters' => [],
        'file_path' => 'exports/other/file.csv',
    ]);

    Storage::disk('local')->put($export->file_path, "Date\n");

    $this->actingAs($user)
        ->get(route('transactions.exports.download', $export))
        ->assertNotFound();
});
