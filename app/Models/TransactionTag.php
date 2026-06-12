<?php

namespace App\Models;

use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Relations\Pivot;
use LogicException;

class TransactionTag extends Pivot
{
    public function save(array $options = []): bool
    {
        $this->guardPostedTransaction();

        return parent::save($options);
    }

    public function delete(): int
    {
        $this->guardPostedTransaction();

        return parent::delete();
    }

    private function guardPostedTransaction(): void
    {
        $transactionId = $this->getAttribute('transaction_id');

        if (! is_numeric($transactionId)) {
            throw new LogicException('Transaction tags require a transaction reference.');
        }

        if (Transaction::query()
            ->whereKey((int) $transactionId)
            ->where('status', TransactionStatus::Posted)
            ->exists()) {
            throw new LogicException('Posted transaction tags are immutable.');
        }
    }
}
