<?php

namespace App\Domain\Transactions;

use App\Domain\Audit\RecordAuditLog;
use App\Enums\AuditAction;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

class UpdateTransactionStatisticsInclusion
{
    public function __construct(
        private readonly RecordAuditLog $recordAuditLog,
    ) {}

    public function update(Transaction $transaction, bool $includeInStatistics, User $actor): Transaction
    {
        if ($transaction->posted_at === null) {
            throw new LogicException('Only posted transactions can change statistics inclusion.');
        }

        return DB::transaction(function () use ($transaction, $includeInStatistics, $actor): Transaction {
            $lockedTransaction = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);

            if ($lockedTransaction->posted_at === null) {
                throw new LogicException('Only posted transactions can change statistics inclusion.');
            }

            if ($lockedTransaction->include_in_statistics === $includeInStatistics) {
                return $lockedTransaction;
            }

            $previousIncludeInStatistics = $lockedTransaction->include_in_statistics;
            $lockedTransaction->update(['include_in_statistics' => $includeInStatistics]);

            $this->recordAuditLog->record(
                $lockedTransaction->workspace,
                AuditAction::TransactionStatisticsInclusionUpdated,
                $lockedTransaction,
                $actor,
                [
                    'previous_include_in_statistics' => $previousIncludeInStatistics,
                    'include_in_statistics' => $includeInStatistics,
                ],
            );

            return $lockedTransaction;
        });
    }
}
