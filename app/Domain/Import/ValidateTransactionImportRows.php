<?php

namespace App\Domain\Import;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Merchant;
use App\Models\Tag;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ValidateTransactionImportRows
{
    /**
     * Validate and resolve the parsed rows against the workspace's reference
     * data, producing a preview/import-ready result for each row.
     *
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array{row: int, status: string, errors: array<int, string>, preview: array<string, string>, data: array<string, mixed>}>
     */
    public function validate(Workspace $workspace, array $rows): array
    {
        $accounts = $workspace->accounts()->whereNull('archived_at')->get()->keyBy(fn ($account): string => strtolower($account->name));
        $categories = $workspace->categories()->whereNull('archived_at')->get()->groupBy(fn ($category): string => strtolower($category->name));
        $merchants = $workspace->merchants()->whereNull('archived_at')->get()->keyBy(fn ($merchant): string => strtolower($merchant->name));
        $tags = $workspace->tags()->whereNull('archived_at')->get()->keyBy(fn ($tag): string => strtolower($tag->name));

        return array_map(
            fn (array $row, int $index): array => $this->validateRow($workspace, $row, $index + 2, $accounts, $categories, $merchants, $tags),
            $rows,
            array_keys($rows),
        );
    }

    /**
     * @param  array<string, string>  $row
     * @param  EloquentCollection<string, Account>  $accounts
     * @param  Collection<string, EloquentCollection<int, Category>>  $categories
     * @param  EloquentCollection<string, Merchant>  $merchants
     * @param  EloquentCollection<string, Tag>  $tags
     * @return array{row: int, status: string, errors: array<int, string>, preview: array<string, string>, data: array<string, mixed>}
     */
    private function validateRow(Workspace $workspace, array $row, int $rowNumber, EloquentCollection $accounts, Collection $categories, EloquentCollection $merchants, EloquentCollection $tags): array
    {
        $errors = [];

        $occurredAt = null;

        if ($row['date'] === '') {
            $errors[] = 'Date is required.';
        } else {
            try {
                $occurredAt = Carbon::createFromFormat('Y-m-d', $row['date'], $workspace->timezone)?->startOfDay();

                if ($occurredAt === null) {
                    $errors[] = 'Date must be in YYYY-MM-DD format.';
                }
            } catch (\Exception) {
                $errors[] = 'Date must be in YYYY-MM-DD format.';
            }
        }

        $type = strtolower($row['type']);
        $transactionType = TransactionType::tryFrom($type);

        if (! in_array($transactionType, [TransactionType::Income, TransactionType::Expense], true)) {
            $errors[] = 'Type must be "income" or "expense".';
            $transactionType = null;
        }

        if ($row['description'] === '') {
            $errors[] = 'Description is required.';
        }

        $account = $accounts->get(strtolower($row['account']));

        if ($row['account'] === '') {
            $errors[] = 'Account is required.';
        } elseif ($account === null) {
            $errors[] = "Account \"{$row['account']}\" was not found.";
        }

        $category = null;

        if ($row['category'] === '') {
            $errors[] = 'Category is required.';
        } else {
            $candidates = $categories->get(strtolower($row['category'])) ?? collect();
            $expectedType = $transactionType === TransactionType::Income ? CategoryType::Income : CategoryType::Expense;
            $category = $candidates->first(fn ($candidate): bool => $transactionType === null || $candidate->type === $expectedType);

            if ($category === null) {
                $errors[] = "Category \"{$row['category']}\" was not found.";
            }
        }

        $amount = null;

        if ($row['amount'] === '') {
            $errors[] = 'Amount is required.';
        } elseif (preg_match('/^\d+$/', $row['amount']) !== 1 || (int) $row['amount'] < 1) {
            $errors[] = 'Amount must be a positive whole number.';
        } else {
            $amount = (int) $row['amount'];
        }

        if ($row['currency'] !== '' && $account !== null && $row['currency'] !== $account->currency_code) {
            $errors[] = "Currency \"{$row['currency']}\" does not match account currency \"{$account->currency_code}\".";
        }

        $merchant = null;

        if ($row['merchant'] !== '') {
            $merchant = $merchants->get(strtolower($row['merchant']));

            if ($merchant === null) {
                $errors[] = "Merchant \"{$row['merchant']}\" was not found.";
            }
        }

        $tagIds = [];
        $tagNames = array_values(array_filter(array_map('trim', preg_split('/[;,]/', $row['tags']) ?: [])));

        foreach ($tagNames as $tagName) {
            $tag = $tags->get(strtolower($tagName));

            if ($tag === null) {
                $errors[] = "Tag \"{$tagName}\" was not found.";

                continue;
            }

            $tagIds[] = $tag->id;
        }

        $data = [];

        if ($errors === [] && $occurredAt !== null && $transactionType !== null && $account !== null && $category !== null && $amount !== null) {
            $data = [
                'account_id' => $account->id,
                'category_id' => $category->id,
                'merchant_id' => $merchant?->id,
                'tag_ids' => $tagIds,
                'type' => $transactionType->value,
                'amount' => $amount,
                'description' => $row['description'],
                'memo' => $row['memo'] !== '' ? $row['memo'] : null,
                'occurred_at' => $occurredAt->toDateString(),
                'idempotency_key' => $this->buildIdempotencyKey($workspace, $occurredAt, $transactionType, $account->id, $category->id, $merchant?->id, $amount, $row['description'], $row['memo'], $tagIds),
            ];
        }

        return [
            'row' => $rowNumber,
            'status' => $errors === [] ? 'valid' : 'error',
            'errors' => $errors,
            'preview' => $row,
            'data' => $data,
        ];
    }

    /**
     * @param  array<int, int>  $tagIds
     */
    private function buildIdempotencyKey(Workspace $workspace, Carbon $occurredAt, TransactionType $type, int $accountId, int $categoryId, ?int $merchantId, int $amount, string $description, string $memo, array $tagIds): string
    {
        sort($tagIds);

        $parts = [
            $workspace->id,
            $occurredAt->toDateString(),
            $type->value,
            $accountId,
            $categoryId,
            $merchantId ?? '',
            $amount,
            $description,
            $memo,
            implode(',', $tagIds),
        ];

        return 'import:'.hash('sha256', implode('|', $parts));
    }
}
