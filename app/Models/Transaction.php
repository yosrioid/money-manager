<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable([
    'workspace_id',
    'created_by',
    'type',
    'status',
    'currency_code',
    'description',
    'occurred_at',
    'posted_at',
])]
class Transaction extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'occurred_at' => 'immutable_datetime',
            'posted_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Workspace, $this>
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<TransactionEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function save(array $options = []): bool
    {
        if ($this->exists) {
            $originalStatus = $this->getRawOriginal('status');
            $newStatus = $this->getAttributes()['status'] ?? null;
            $dirty = array_keys($this->getDirty());

            if (in_array($originalStatus, [
                TransactionStatus::Voided->value,
                TransactionStatus::Reversed->value,
                TransactionStatus::Replaced->value,
            ], true)) {
                throw new LogicException('Transactions in a terminal state are immutable.');
            }

            if ($originalStatus === TransactionStatus::Posted->value) {
                $allowedStatuses = [TransactionStatus::Reversed->value, TransactionStatus::Replaced->value];

                if ($dirty !== ['status'] || ! in_array($newStatus, $allowedStatuses, true)) {
                    throw new LogicException('Posted transactions are immutable.');
                }
            }

            if ($originalStatus === TransactionStatus::Draft->value && $newStatus === TransactionStatus::Voided->value && $dirty !== ['status']) {
                throw new LogicException('Voiding a draft transaction cannot include other changes.');
            }
        }

        return parent::save($options);
    }

    public function delete(): ?bool
    {
        throw new LogicException('Posted transactions are immutable.');
    }
}
