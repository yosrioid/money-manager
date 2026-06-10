<?php

namespace App\Http\Controllers;

use App\Domain\Ordering\MoveOrderedResource;
use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\CategoryType;
use App\Http\Requests\MoveOrderedResourceRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly WorkspaceContext $workspaceContext,
    ) {}

    public function index(): Response
    {
        $workspace = $this->workspaceContext->get();

        return Inertia::render('categories/Index', [
            'incomeCategories' => $workspace->categories()
                ->where('type', CategoryType::Income)
                ->active()
                ->topLevel()
                ->with(['subcategories' => fn ($query) => $query->active()->orderBy('position')])
                ->orderBy('position')
                ->get(),
            'expenseCategories' => $workspace->categories()
                ->where('type', CategoryType::Expense)
                ->active()
                ->topLevel()
                ->with(['subcategories' => fn ($query) => $query->active()->orderBy('position')])
                ->orderBy('position')
                ->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $workspace = $this->workspaceContext->get();

        $maxPosition = $workspace->categories()
            ->where('type', $request->validated('type'))
            ->where('parent_id', $request->validated('parent_id'))
            ->max('position') ?? -1;

        $workspace->categories()->create([
            ...$request->validated(),
            'position' => $maxPosition + 1,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return to_route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();
        $parentId = array_key_exists('parent_id', $validated)
            ? $validated['parent_id']
            : $category->parent_id;

        if ($category->getRawOriginal('type') !== $validated['type'] || $category->parent_id !== $parentId) {
            $validated['position'] = ($this->workspaceContext->get()->categories()
                ->where('type', $validated['type'])
                ->where('parent_id', $parentId)
                ->max('position') ?? -1) + 1;
        }

        $category->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return to_route('categories.index');
    }

    public function move(
        MoveOrderedResourceRequest $request,
        Category $category,
        MoveOrderedResource $moveOrderedResource,
    ): RedirectResponse {
        $this->authorize('update', $category);

        $moveOrderedResource->move(
            $category,
            $this->workspaceContext->get()->categories()
                ->active()
                ->where('type', $category->type)
                ->where('parent_id', $category->parent_id)
                ->getQuery(),
            $request->string('direction')->value(),
        );

        return to_route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $category->update(['archived_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category archived.')]);

        return to_route('categories.index');
    }
}
