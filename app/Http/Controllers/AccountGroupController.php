<?php

namespace App\Http\Controllers;

use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\StoreAccountGroupRequest;
use App\Http\Requests\UpdateAccountGroupRequest;
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

        return Inertia::render('accounts/Index', [
            'accountGroups' => $workspace->accountGroups()
                ->withCount('accounts')
                ->orderBy('position')
                ->get(),
            'accounts' => $workspace->accounts()
                ->with('accountGroup:id,name')
                ->active()
                ->orderBy('position')
                ->get(),
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

    public function destroy(AccountGroup $accountGroup): RedirectResponse
    {
        $this->authorize('delete', $accountGroup);

        $accountGroup->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account group deleted.')]);

        return to_route('accounts.index');
    }
}
