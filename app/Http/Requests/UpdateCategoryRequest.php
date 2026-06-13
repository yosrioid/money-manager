<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->attributes->get('workspace');
        $category = $this->route('category');

        abort_unless($workspace instanceof Workspace, 403);
        abort_unless($category instanceof Category, 404);

        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::notIn([$category->id]),
                Rule::exists('categories', 'id')
                    ->where('workspace_id', $workspace->id)
                    ->where('type', $this->input('type'))
                    ->whereNull('parent_id'),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $category = $this->route('category');

                if (! $category instanceof Category) {
                    return;
                }

                if ($this->filled('parent_id') && $category->subcategories()->exists()) {
                    $validator->errors()->add(
                        'parent_id',
                        __('A category with subcategories cannot become a subcategory.'),
                    );
                }

                if ($category->getRawOriginal('type') !== $this->input('type')
                    && $category->subcategories()->exists()) {
                    $validator->errors()->add(
                        'type',
                        __('A category with subcategories cannot change type.'),
                    );
                }
            },
        ];
    }
}
