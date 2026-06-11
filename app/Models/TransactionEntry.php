<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable([
    'transaction_id',
    'workspace_id',
    'account_id',
    'category_id',
    'type',
    'currency_code',
    'amount',
])]
class TransactionEntry extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'amount' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Transaction, $this>
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new LogicException('Posted transaction entries are immutable.');
        }

        if (Transaction::query()
            ->whereKey($this->transaction_id)
            ->where('status', TransactionStatus::Posted)
            ->exists()) {
            throw new LogicException('Posted transactions cannot accept new entries.');
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new LogicException('Posted transaction entries are immutable.');
    }
}
