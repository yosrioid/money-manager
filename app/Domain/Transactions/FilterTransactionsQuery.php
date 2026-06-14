<?php

namespace App\Domain\Transactions;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\Workspace;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

class FilterTransactionsQuery
{
    /**
     * Resolve the transaction filter parameters from the request.
     *
     * @return array{search: string, type: ?string, status: ?string, category_id: ?int, account_id: ?int, tag_id: ?int, from: ?CarbonInterface, to: ?CarbonInterface}
     */
    public function resolve(Request $request, Workspace $workspace): array
    {
        $type = $request->input('type');
        $type = is_string($type) && TransactionType::tryFrom($type) !== null ? $type : null;

        $status = $request->input('status');
        $status = is_string($status) && TransactionStatus::tryFrom($status) !== null ? $status : null;

        $categoryId = $request->input('category_id');
        $categoryId = is_numeric($categoryId) ? (int) $categoryId : null;

        $accountId = $request->input('account_id');
        $accountId = is_numeric($accountId) ? (int) $accountId : null;

        $tagId = $request->input('tag_id');
        $tagId = is_numeric($tagId) ? (int) $tagId : null;

        return [
            'search' => trim((string) $request->input('q', '')),
            'type' => $type,
            'status' => $status,
            'category_id' => $categoryId,
            'account_id' => $accountId,
            'tag_id' => $tagId,
            'from' => $this->parseStrictDate($request->input('from'), $workspace),
            'to' => $this->parseStrictDate($request->input('to'), $workspace),
        ];
    }

    /**
     * Convert resolved filters into a JSON-serializable array suitable for
     * storing alongside a queued export.
     *
     * @param  array{search: string, type: ?string, status: ?string, category_id: ?int, account_id: ?int, tag_id: ?int, from: ?CarbonInterface, to: ?CarbonInterface}  $filters
     * @return array{search: string, type: ?string, status: ?string, category_id: ?int, account_id: ?int, tag_id: ?int, from: ?string, to: ?string}
     */
    public function serialize(array $filters): array
    {
        return [
            ...$filters,
            'from' => $filters['from']?->toDateString(),
            'to' => $filters['to']?->toDateString(),
        ];
    }

    /**
     * Convert a serialized filter array (as stored on a queued export) back
     * into resolved filters with workspace-local `from`/`to` dates.
     *
     * @param  array<string, mixed>  $filters
     * @return array{search: string, type: ?string, status: ?string, category_id: ?int, account_id: ?int, tag_id: ?int, from: ?CarbonInterface, to: ?CarbonInterface}
     */
    public function hydrate(array $filters, Workspace $workspace): array
    {
        $from = $filters['from'] ?? null;
        $to = $filters['to'] ?? null;

        return [
            'search' => is_string($filters['search'] ?? null) ? $filters['search'] : '',
            'type' => is_string($filters['type'] ?? null) ? $filters['type'] : null,
            'status' => is_string($filters['status'] ?? null) ? $filters['status'] : null,
            'category_id' => is_int($filters['category_id'] ?? null) ? $filters['category_id'] : null,
            'account_id' => is_int($filters['account_id'] ?? null) ? $filters['account_id'] : null,
            'tag_id' => is_int($filters['tag_id'] ?? null) ? $filters['tag_id'] : null,
            'from' => is_string($from) ? Carbon::parse($from, $workspace->timezone)->startOfDay() : null,
            'to' => is_string($to) ? Carbon::parse($to, $workspace->timezone)->startOfDay() : null,
        ];
    }

    /**
     * Build the posted-transaction query shared by the transaction history,
     * CSV export, and queued export views, applying the resolved search and
     * filter parameters.
     *
     * @param  array{search: string, type: ?string, status: ?string, category_id: ?int, account_id: ?int, tag_id: ?int, from: ?CarbonInterface, to: ?CarbonInterface}  $filters
     * @return HasMany<Transaction, Workspace>
     */
    public function query(Workspace $workspace, array $filters): HasMany
    {
        $query = $workspace->transactions()->whereNotNull('posted_at');

        if ($filters['search'] !== '') {
            $search = $filters['search'];

            $query->where(function (Builder $query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('memo', 'like', "%{$search}%")
                    ->orWhereHas('merchant', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('entries.account', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('entries.category', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));

                if (preg_match('/^-?\d+$/', $search) === 1) {
                    $amount = abs((int) $search);
                    $query->orWhereHas('entries', fn (Builder $q) => $q->whereRaw('abs(amount) = ?', [$amount]));
                }
            });
        }

        if ($filters['type'] !== null) {
            $query->where('type', $filters['type']);
        }

        if ($filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if ($filters['category_id'] !== null) {
            $query->whereHas('entries', fn (Builder $q) => $q->where('category_id', $filters['category_id']));
        }

        if ($filters['account_id'] !== null) {
            $query->whereHas('entries', fn (Builder $q) => $q->where('account_id', $filters['account_id']));
        }

        if ($filters['tag_id'] !== null) {
            $query->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $filters['tag_id']));
        }

        if ($filters['from'] !== null) {
            $query->where('occurred_at', '>=', $filters['from']->copy()->utc());
        }

        if ($filters['to'] !== null) {
            $query->where('occurred_at', '<', $filters['to']->copy()->addDay()->utc());
        }

        return $query;
    }

    /**
     * Strictly parse a `YYYY-MM-DD` workspace-local date query parameter,
     * rejecting calendar-invalid dates (e.g. `2026-02-31`) and malformed
     * values (e.g. `2026-99-01`) instead of silently normalizing or throwing.
     */
    private function parseStrictDate(mixed $value, Workspace $workspace): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        if (! checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            return null;
        }

        return Carbon::parse($value, $workspace->timezone)->startOfDay();
    }
}
