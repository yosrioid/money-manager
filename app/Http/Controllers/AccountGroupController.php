<?php

namespace App\Http\Controllers;

use App\Domain\Accounts\CalculateCardOutstandingBalance;
use App\Domain\Accounts\CalculateDebtPayoffProgress;
use App\Domain\Accounts\SummarizeInstallmentPlan;
use App\Domain\Ordering\MoveOrderedResource;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\AccountType;
use App\Http\Requests\MoveOrderedResourceRequest;
use App\Http\Requests\StoreAccountGroupRequest;
use App\Http\Requests\UpdateAccountGroupRequest;
use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\InstallmentPlan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountGroupController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
        private readonly CalculateCardOutstandingBalance $calculateCardOutstandingBalance,
        private readonly SummarizeInstallmentPlan $summarizeInstallmentPlan,
        private readonly CalculateDebtPayoffProgress $calculateDebtPayoffProgress,
    ) {}

    public function index(): Response
    {
        $workspace = $this->workspaceContext->get();
        $today = Carbon::now($workspace->timezone);

        $accounts = $workspace->accounts()
            ->with('accountGroup:id,name', 'linkedAccount:id,name', 'installmentPlans')
            ->withSum('postedLedgerEntries as balance', 'amount')
            ->active()
            ->orderBy(
                AccountGroup::query()
                    ->select('position')
                    ->whereColumn('account_groups.id', 'accounts.account_group_id'),
            )
            ->orderBy('position')
            ->get()
            ->each(function (Account $account) use ($today): void {
                $account->setAttribute('balance', (int) ($account->getAttribute('balance') ?? 0));

                $this->applyCardOutstandingBalance($account, $today);
                $this->applyInstallmentPlans($account, $today);
                $this->applyDebtPayoffProgress($account);
            });

        return Inertia::render('accounts/Index', [
            'accountGroups' => $workspace->accountGroups()
                ->withCount('accounts')
                ->active()
                ->orderBy('position')
                ->get(),
            'accounts' => $accounts,
        ]);
    }

    private function applyCardOutstandingBalance(Account $account, Carbon $today): void
    {
        if ($account->getAttribute('type') !== AccountType::CreditCard
            || $account->statement_closing_day === null
            || $account->payment_due_day === null) {
            return;
        }

        $outstanding = $this->calculateCardOutstandingBalance->calculate($account, $today);

        $account->setAttribute('statement_outstanding', $outstanding['statement']);
        $account->setAttribute('statement_closing_date', $outstanding['statement_closing_date']->toDateString());
        $account->setAttribute('payment_due_date', $outstanding['payment_due_date']->toDateString());
    }

    private function applyInstallmentPlans(Account $account, Carbon $today): void
    {
        if ($account->getAttribute('type') !== AccountType::CreditCard) {
            return;
        }

        $account->setAttribute('installment_plans', $account->installmentPlans
            ->map(fn (InstallmentPlan $plan) => $this->summarizeInstallmentPlan->summarize($plan, $today))
            ->values());
    }

    private function applyDebtPayoffProgress(Account $account): void
    {
        if ($account->getAttribute('type') !== AccountType::Loan) {
            return;
        }

        $account->setAttribute('debt_payoff', $this->calculateDebtPayoffProgress->calculate($account));
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
