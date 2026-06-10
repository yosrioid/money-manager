<?php

namespace App\Domain\Accounts;

use App\Domain\Ledger\PostOpeningBalance;
use App\Models\Account;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class CreateAccountWithOpeningBalance
{
    public function __construct(
        private readonly PostOpeningBalance $postOpeningBalance,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(Workspace $workspace, array $attributes, User $actor): Account
    {
        if (! $workspace->memberships()->where('user_id', $actor->id)->exists()) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($workspace, $attributes, $actor): Account {
            $openingBalance = (int) $attributes['opening_balance'];
            unset($attributes['opening_balance']);

            $maxPosition = $workspace->accounts()
                ->where('account_group_id', $attributes['account_group_id'] ?? null)
                ->max('position') ?? -1;

            $account = $workspace->accounts()->create([
                ...$attributes,
                'position' => $maxPosition + 1,
            ]);

            $this->postOpeningBalance->post($account, $openingBalance, $actor);

            return $account;
        });
    }
}
