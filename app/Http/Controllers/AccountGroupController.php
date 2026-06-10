<?php

namespace App\Http\Controllers;

use App\Domain\Ordering\MoveOrderedResource;
use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\MoveOrderedResourceRequest;
use App\Http\Requests\StoreAccountGroupRequest;
use App\Http\Requests\UpdateAccountGroupRequest;
use App\Models\Account;
use App\Models\AccountGroup;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountGroupController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(): Response
    {
        $workspace = $this->workspaceContext->get();
        $accounts = $workspace->accounts()
            ->with('accountGroup:id,name')
            ->withSum('ledgerEntries as balance', 'amount')
            ->active()
            ->orderBy(
                AccountGroup::query()
                    ->select('position')
                    ->whereColumn('account_groups.id', 'accounts.account_group_id'),
            )
            ->orderBy('position')
            ->get()
            ->each(fn (Account $account) => $account->setAttribute(
                'balance',
                (int) ($account->getAttribute('balance') ?? 0),
            ));

        return Inertia::render('accounts/Index', [
            'accountGroups' => $workspace->accountGroups()
                ->withCount('accounts')
                ->active()
                ->orderBy('position')
                ->get(),
            'accounts' => $accounts,
        ]);
    }

    public function store(StoreAccountGroupRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $maxPosition = $workspace->accountGroups()->max('position') ?? -1;

        $workspace->accountGroups()->create([
            ...$request->validated(),
            'position' => $maxPosition + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account group created.')]);

        return to_route('accounts.index');
    }

    public function update(UpdateAccountGroupRequest $request, AccountGroup $accountGroup): RedirectResponse
    {
        $accountGroup->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account group updated.')]);

        return to_route('accounts.index');
    }

    public function move(
        MoveOrderedResourceRequest $request,
        AccountGroup $accountGroup,
        MoveOrderedResource $moveOrderedResource,
    ): RedirectResponse {
        $this->authorize('update', $accountGroup);

        $moveOrderedResource->move(
            $accountGroup,
            $this->workspaceContext->get()->accountGroups()->active()->getQuery(),
            $request->string('direction')->value(),
        );

        return to_route('accounts.index');
    }

    public function destroy(AccountGroup $accountGroup): RedirectResponse
    {
        $this->authorize('delete', $accountGroup);

        $accountGroup->update(['archived_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account group archived.')]);

        return to_route('accounts.index');
    }
}
