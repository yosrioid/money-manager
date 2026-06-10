<?php

namespace App\Http\Controllers;

use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\AccountType;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use App\Models\Currency;
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

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $maxPosition = $workspace->accounts()->max('position') ?? -1;

        $workspace->accounts()->create([
            ...$request->validated(),
            'position' => $maxPosition + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account created.')]);

        return to_route('accounts.index');
    }

    public function edit(Account $account): Response
    {
        $this->authorize('update', $account);

        $workspace = $this->workspaceContext->get();

        return Inertia::render('accounts/EditAccount', [
            'account' => $account,
            'accountGroups' => $workspace->accountGroups()->active()->orderBy('position')->get(['id', 'name']),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name', 'symbol']),
            'accountTypes' => $this->accountTypes(),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        $account->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account updated.')]);

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
