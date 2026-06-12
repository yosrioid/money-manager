<?php

namespace App\Domain\Transactions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;

class SaveTransactionDraft
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function save(Workspace $workspace, User $actor, array $data): Transaction
    {
        if (! $workspace->memberships()->where('user_id', $actor->id)->exists()) {
            throw new AuthorizationException;
        }

        $type = TransactionType::tryFrom((string) ($data['type'] ?? '')) ?? TransactionType::Expense;
        $account = isset($data['account_id']) ? $workspace->accounts()->find($data['account_id']) : null;
        $currencyCode = $account instanceof Account ? $account->currency_code : $workspace->default_currency;

        return Transaction::query()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $actor->id,
            'type' => $type,
            'status' => TransactionStatus::Draft,
            'currency_code' => $currencyCode,
            'description' => filled($data['description'] ?? null) ? $data['description'] : 'Untitled draft',
            'occurred_at' => filled($data['occurred_at'] ?? null)
                ? Carbon::parse($data['occurred_at'], $workspace->timezone)->utc()
                : now(),
            'draft_data' => $data,
        ]);
    }
}
