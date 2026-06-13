<?php

namespace App\Http\Controllers;

use App\Domain\Ordering\MoveOrderedResource;
use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\MoveOrderedResourceRequest;
use App\Http\Requests\StoreTransactionBookmarkRequest;
use App\Http\Requests\UpdateTransactionBookmarkRequest;
use App\Models\TransactionBookmark;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class TransactionBookmarkController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function store(StoreTransactionBookmarkRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();
        $validated = $request->validated();
        $name = $validated['name'];
        unset($validated['name']);

        $maxPosition = $workspace->transactionBookmarks()->max('position') ?? -1;

        $workspace->transactionBookmarks()->create([
            'name' => $name,
            'payload' => $validated,
            'position' => $maxPosition + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction bookmark saved.')]);

        return back();
    }

    public function update(UpdateTransactionBookmarkRequest $request, TransactionBookmark $transactionBookmark): RedirectResponse
    {
        $transactionBookmark->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction bookmark renamed.')]);

        return back();
    }

    public function move(
        MoveOrderedResourceRequest $request,
        TransactionBookmark $transactionBookmark,
        MoveOrderedResource $moveOrderedResource,
    ): RedirectResponse {
        $this->authorize('update', $transactionBookmark);

        $moveOrderedResource->move(
            $transactionBookmark,
            $this->workspaceContext->get()->transactionBookmarks()->getQuery(),
            $request->string('direction')->value(),
        );

        return back();
    }

    public function destroy(TransactionBookmark $transactionBookmark): RedirectResponse
    {
        $this->authorize('delete', $transactionBookmark);

        $transactionBookmark->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Transaction bookmark deleted.')]);

        return back();
    }
}
