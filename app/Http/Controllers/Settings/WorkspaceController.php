<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateWorkspaceExchangeRatesRequest;
use App\Http\Requests\Settings\WorkspacePreferencesRequest;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function edit(): Response
    {
        $workspace = $this->workspaceContext->get();

        return Inertia::render('settings/Workspace', [
            'workspace' => $workspace->only([
                'id',
                'name',
                'default_currency',
                'timezone',
                'locale',
                'number_format',
                'first_day_of_week',
                'month_start_day',
                'adjust_month_for_weekend',
                'application_lock_minutes',
                'navigation_shortcuts_enabled',
                'net_asset_target',
            ]),
            'entryFormFields' => $workspace->entryFormFields(),
            'reportWidgets' => $workspace->reportWidgets(),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name', 'symbol']),
            'exchangeRates' => $workspace->exchangeRates()->orderBy('currency_code')->get(['currency_code', 'rate_to_base']),
            'accountCurrencies' => $workspace->accounts()
                ->select('currency_code')
                ->distinct()
                ->where('currency_code', '!=', $workspace->default_currency)
                ->orderBy('currency_code')
                ->pluck('currency_code'),
        ]);
    }

    public function update(WorkspacePreferencesRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $this->authorize('update', $workspace);

        $validated = $request->validated();
        $validated['entry_form_fields'] = $validated['entry_form_fields'] ?? [];
        $validated['report_widgets'] = $validated['report_widgets'] ?? [];

        $workspace->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace settings updated.')]);

        return to_route('workspace.edit');
    }

    public function updateExchangeRates(UpdateWorkspaceExchangeRatesRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $this->authorize('update', $workspace);

        $rates = $request->validated('exchange_rates', []);

        $workspace->exchangeRates()->delete();
        $workspace->exchangeRates()->createMany(array_map(fn (array $rate): array => [
            'currency_code' => $rate['currency_code'],
            'rate_to_base' => $rate['rate_to_base'],
        ], array_filter($rates, fn (array $rate): bool => filled($rate['rate_to_base'] ?? null))));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Exchange rates updated.')]);

        return to_route('workspace.edit');
    }
}
