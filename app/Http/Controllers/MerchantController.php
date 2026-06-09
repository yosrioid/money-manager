<?php

namespace App\Http\Controllers;

use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\StoreMerchantRequest;
use App\Http\Requests\UpdateMerchantRequest;
use App\Models\Merchant;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MerchantController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(): Response
    {
        $workspace = $this->workspaceContext->get();

        return Inertia::render('merchants/Index', [
            'merchants' => $workspace->merchants()
                ->active()
                ->with('defaultCategory:id,name,type')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreMerchantRequest $request): RedirectResponse
    {
        $this->workspaceContext->get()->merchants()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merchant created.')]);

        return to_route('merchants.index');
    }

    public function update(UpdateMerchantRequest $request, Merchant $merchant): RedirectResponse
    {
        $merchant->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merchant updated.')]);

        return to_route('merchants.index');
    }

    public function destroy(Merchant $merchant): RedirectResponse
    {
        $this->authorize('delete', $merchant);

        $merchant->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Merchant deleted.')]);

        return to_route('merchants.index');
    }
}
