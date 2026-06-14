<?php

namespace App\Domain\Import;

use App\Domain\Transactions\RecordIncomeExpense;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;

class ImportTransactions
{
    public function __construct(
        private readonly RecordIncomeExpense $recordIncomeExpense,
    ) {}

    /**
     * Import the previously validated rows, skipping invalid rows and rows
     * that were already imported (matched by idempotency key).
     *
     * @param  array<int, array{row: int, status: string, errors: array<int, string>, preview: array<string, string>, data: array<string, mixed>}>  $rows
     * @return array{imported: int, duplicated: int, invalid: int}
     */
    public function import(Workspace $workspace, User $actor, array $rows): array
    {
        $imported = 0;
        $duplicated = 0;
        $invalid = 0;

        foreach ($rows as $row) {
            if ($row['status'] !== 'valid') {
                $invalid++;

                continue;
            }

            $data = $row['data'];

            $alreadyImported = Transaction::query()
                ->where('workspace_id', $workspace->id)
                ->where('idempotency_key', $data['idempotency_key'])
                ->exists();

            if ($alreadyImported) {
                $duplicated++;

                continue;
            }

            $account = Account::query()->whereKey($data['account_id'])->firstOrFail();
            $category = Category::query()->whereKey($data['category_id'])->firstOrFail();
            $merchant = $data['merchant_id'] === null ? null : Merchant::query()->whereKey($data['merchant_id'])->firstOrFail();
            $tags = Tag::query()->whereIn('id', $data['tag_ids'])->get()->all();

            $this->recordIncomeExpense->record(
                $account,
                $category,
                TransactionType::from($data['type']),
                $data['amount'],
                $data['description'],
                Carbon::parse($data['occurred_at'], $workspace->timezone),
                $actor,
                $merchant,
                $data['memo'],
                $tags,
                $data['idempotency_key'],
            );

            $imported++;
        }

        return ['imported' => $imported, 'duplicated' => $duplicated, 'invalid' => $invalid];
    }
}
