<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\TransactionStatus;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'account_group_id',
    'name',
    'type',
    'currency_code',
    'description',
    'position',
    'is_visible',
    'is_favorite',
    'include_in_total',
    'archived_at',
])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'is_visible' => 'boolean',
            'is_favorite' => 'boolean',
            'include_in_total' => 'boolean',
            'archived_at' => 'datetime',
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
     * @return BelongsTo<AccountGroup, $this>
     */
    public function accountGroup(): BelongsTo
    {
        return $this->belongsTo(AccountGroup::class);
    }

    /**
     * @return HasMany<TransactionEntry, $this>
     */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    /**
     * @return HasMany<TransactionEntry, $this>
     */
    public function postedLedgerEntries(): HasMany
    {
        return $this->ledgerEntries()
            ->whereHas('transaction', fn (Builder $query) => $query->whereNotNull('posted_at')
                ->where('status', '!=', TransactionStatus::Replaced));
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<Account>  $query
     * @return Builder<Account>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
