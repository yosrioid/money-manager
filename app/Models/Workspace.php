<?php

namespace App\Models;

use Database\Factories\WorkspaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'owner_id',
    'name',
    'default_currency',
    'timezone',
    'locale',
    'number_format',
    'first_day_of_week',
    'month_start_day',
    'adjust_month_for_weekend',
    'application_lock_minutes',
    'entry_form_fields',
    'navigation_shortcuts_enabled',
    'net_asset_target',
    'report_widgets',
])]
class Workspace extends Model
{
    /** @use HasFactory<WorkspaceFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<WorkspaceMember, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(WorkspaceMember::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'first_day_of_week' => 'integer',
            'month_start_day' => 'integer',
            'adjust_month_for_weekend' => 'boolean',
            'application_lock_minutes' => 'integer',
            'entry_form_fields' => 'array',
            'navigation_shortcuts_enabled' => 'boolean',
            'net_asset_target' => 'integer',
            'report_widgets' => 'array',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function entryFormFields(): array
    {
        return $this->entry_form_fields ?? self::defaultEntryFormFields();
    }

    /**
     * @return array<int, string>
     */
    public static function defaultEntryFormFields(): array
    {
        return ['merchant', 'memo', 'tags'];
    }

    /**
     * @return array<int, string>
     */
    public function reportWidgets(): array
    {
        return $this->report_widgets ?? self::defaultReportWidgets();
    }

    /**
     * @return array<int, string>
     */
    public static function defaultReportWidgets(): array
    {
        return ['summary', 'comparison', 'categoryBreakdown', 'merchantBreakdown', 'accountActivity', 'netWorth', 'netWorthTrend'];
    }

    /**
     * @return HasMany<AccountGroup, $this>
     */
    public function accountGroups(): HasMany
    {
        return $this->hasMany(AccountGroup::class);
    }

    /**
     * @return HasMany<Account, $this>
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Merchant, $this>
     */
    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    /**
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return HasMany<Transaction, $this>
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return HasMany<TransactionExport, $this>
     */
    public function transactionExports(): HasMany
    {
        return $this->hasMany(TransactionExport::class);
    }

    /**
     * @return HasMany<TransactionEntry, $this>
     */
    public function transactionEntries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * @return HasMany<DayNote, $this>
     */
    public function dayNotes(): HasMany
    {
        return $this->hasMany(DayNote::class);
    }

    /**
     * @return HasMany<TransactionBookmark, $this>
     */
    public function transactionBookmarks(): HasMany
    {
        return $this->hasMany(TransactionBookmark::class);
    }

    /**
     * @return HasMany<CategoryBudgetOverride, $this>
     */
    public function categoryBudgetOverrides(): HasMany
    {
        return $this->hasMany(CategoryBudgetOverride::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }
}
