<?php

namespace App\Http\Controllers;

use App\Domain\Accounts\CreateAccountWithOpeningBalance;
use App\Domain\Ordering\MoveOrderedResource;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\AccountType;
use App\Http\Requests\MoveOrderedResourceRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function create(): Response
    {
        $workspace = $this->workspaceContext->get();

        return Inertia::render('accounts/CreateAccount', [
            'accountGroups' => $workspace->accountGroups()->active()->orderBy('position')->get(['id', 'name']),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name', 'symbol']),
            'defaultCurrency' => $workspace->default_currency,
            'accountTypes' => $this->accountTypes(),
        ]);
    }

    public function store(
        StoreAccountRequest $request,
        CreateAccountWithOpeningBalance $createAccountWithOpeningBalance,
    ): RedirectResponse {
        $workspace = $this->workspaceContext->get();
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $createAccountWithOpeningBalance->create($workspace, $request->validated(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created.')]);

        return to_route('accounts.index');
    }

    public function edit(Account $account): Response
    {
        $this->authorize('update', $account);

        $workspace = $this->workspaceContext->get();
        $account->loadSum('postedLedgerEntries as balance', 'amount');
        $account->setAttribute('balance', (int) ($account->getAttribute('balance') ?? 0));

        return Inertia::render('accounts/EditAccount', [
            'account' => $account,
            'accountGroups' => $workspace->accountGroups()->active()->orderBy('position')->get(['id', 'name']),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name', 'symbol']),
            'accountTypes' => $this->accountTypes(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $validated = $request->validated();

        if (AccountType::tryFrom($validated['type']) !== AccountType::CreditCard) {
            $validated['credit_limit'] = null;
            $validated['statement_closing_day'] = null;
            $validated['payment_due_day'] = null;
        }

        $accountGroupId = array_key_exists('account_group_id', $validated)
            ? $validated['account_group_id']
            : $account->account_group_id;

        if ($account->account_group_id !== $accountGroupId) {
            $validated['position'] = ($this->workspaceContext->get()->accounts()
                ->where('account_group_id', $accountGroupId)
                ->max('position') ?? -1) + 1;
        }

        $account->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account updated.')]);

        return to_route('accounts.index');
    }

    public function move(
        MoveOrderedResourceRequest $request,
        Account $account,
        MoveOrderedResource $moveOrderedResource,
    ): RedirectResponse {
        $this->authorize('update', $account);

        $moveOrderedResource->move(
            $account,
            $this->workspaceContext->get()->accounts()
                ->active()
                ->where('account_group_id', $account->account_group_id)
                ->getQuery(),
            $request->string('direction')->value(),
        );

        return to_route('accounts.index');
    }

    public function destroy(Account $account): RedirectResponse
    {
        $this->authorize('delete', $account);

        $account->update(['archived_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account archived.')]);

        return to_route('accounts.index');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function accountTypes(): array
    {
        return array_map(
            fn ($type): array => [
                'value' => $type->value,
                'label' => Str::headline($type->value),
            ],
            AccountType::cases(),
        );
    }
}
