<?php

namespace App\Http\Controllers;

use App\Domain\Workspaces\WorkspaceContext;
use App\Enums\CategoryType;
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
            ->whereNull('parent_id')
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
        $category->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

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
