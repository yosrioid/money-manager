<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Controllers\Controller;
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
            ]),
            'currencies' => Currency::query()->orderBy('code')->get(['code', 'name', 'symbol']),
        ]);
    }

    public function update(WorkspacePreferencesRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $this->authorize('update', $workspace);

        $workspace->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace settings updated.')]);

        return to_route('workspace.edit');
    }
}
