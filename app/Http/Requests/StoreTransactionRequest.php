<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->attributes->get('workspace');

        abort_unless($workspace instanceof Workspace, 403);

        return [
            'type' => ['required', Rule::in([TransactionType::Income->value, TransactionType::Expense->value])],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('workspace_id', $workspace->id)],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->input('type');
            $categoryId = $this->input('category_id');

            if (! is_string($type) || ! is_numeric($categoryId)) {
                return;
            }

            $category = Category::query()->find($categoryId);

            if (! $category instanceof Category) {
                return;
            }

            $expectedType = $type === TransactionType::Income->value ? CategoryType::Income : CategoryType::Expense;

            if ($category->getRawOriginal('type') !== $expectedType->value) {
                $validator->errors()->add('category_id', 'The selected category does not match the transaction type.');
            }
        });
    }
}
