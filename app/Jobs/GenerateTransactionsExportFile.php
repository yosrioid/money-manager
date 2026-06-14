<?php

namespace App\Jobs;

use App\Domain\Export\GenerateTransactionsCsv;
use App\Domain\Transactions\FilterTransactionsQuery;
use App\Enums\TransactionExportStatus;
use App\Models\TransactionExport;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateTransactionsExportFile implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $transactionExportId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(FilterTransactionsQuery $filterTransactionsQuery, GenerateTransactionsCsv $generateTransactionsCsv): void
    {
        $export = TransactionExport::query()->find($this->transactionExportId);

        if (! $export instanceof TransactionExport) {
            return;
        }

        $workspace = $export->workspace;
        $creator = $export->creator;

        $isAuthorized = $creator !== null
            && $workspace->memberships()->where('user_id', $creator->id)->exists();

        if (! $isAuthorized) {
            $export->update([
                'status' => TransactionExportStatus::Failed,
                'failed_reason' => 'The export could not be authorized.',
            ]);

            return;
        }

        $export->update(['status' => TransactionExportStatus::Processing]);

        try {
            $storedFilters = json_decode((string) $export->getRawOriginal('filters'), true);
            $filters = $filterTransactionsQuery->hydrate(is_array($storedFilters) ? $storedFilters : [], $workspace);
            $query = $filterTransactionsQuery->query($workspace, $filters);

            $directory = "exports/{$workspace->id}";
            Storage::disk('local')->makeDirectory($directory);

            $relativePath = "{$directory}/".Str::uuid()->toString().'.csv';
            $generateTransactionsCsv->writeToFile(Storage::disk('local')->path($relativePath), $workspace, $query);

            $export->update([
                'status' => TransactionExportStatus::Ready,
                'file_path' => $relativePath,
                'ready_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $export->update([
                'status' => TransactionExportStatus::Failed,
                'failed_reason' => 'The export could not be generated.',
            ]);

            throw $exception;
        }
    }
}
