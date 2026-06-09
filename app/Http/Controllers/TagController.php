<?php

namespace App\Http\Controllers;

use App\Domain\Workspaces\WorkspaceContext;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(): Response
    {
        $workspace = $this->workspaceContext->get();

        return Inertia::render('tags/Index', [
            'tags' => $workspace->tags()->active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreTagRequest $request): RedirectResponse
    {
        $this->workspaceContext->get()->tags()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag created.')]);

        return to_route('tags.index');
    }

    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $tag->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag updated.')]);

        return to_route('tags.index');
    }

    public function destroy(Tag $tag): RedirectResponse
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Tag deleted.')]);

        return to_route('tags.index');
    }
}
