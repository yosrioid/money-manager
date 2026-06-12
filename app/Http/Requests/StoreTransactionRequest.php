<?php

namespace App\Http\Requests;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
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
            'type' => ['required', Rule::in([TransactionType::Income->value, TransactionType::Expense->value, TransactionType::Transfer->value])],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('workspace_id', $workspace->id)],
            'category_id' => ['required_unless:type,transfer', 'prohibited_if:type,transfer', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'destination_account_id' => ['required_if:type,transfer', 'prohibited_unless:type,transfer', 'integer', Rule::exists('accounts', 'id')->where('workspace_id', $workspace->id)],
            'fee_amount' => ['nullable', 'prohibited_unless:type,transfer', 'integer', 'min:0'],
            'fee_category_id' => ['nullable', 'prohibited_unless:type,transfer', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:255'],
            'occurred_at' => ['required', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $type = $this->input('type');
            $workspace = $this->attributes->get('workspace');

            if (! is_string($type) || ! $workspace instanceof Workspace) {
                return;
            }

            if ($type === TransactionType::Transfer->value) {
                $this->validateTransfer($validator);

                return;
            }

            $categoryId = $this->input('category_id');
            $category = is_numeric($categoryId)
                ? Category::query()->where('workspace_id', $workspace->id)->find($categoryId)
                : null;

            if (! $category instanceof Category) {
                return;
            }

            $expectedType = $type === TransactionType::Income->value ? CategoryType::Income : CategoryType::Expense;

            if ($category->getRawOriginal('type') !== $expectedType->value) {
                $validator->errors()->add('category_id', 'The selected category does not match the transaction type.');
            }
        }];
    }

    private function validateTransfer(Validator $validator): void
    {
        $workspace = $this->attributes->get('workspace');

        if (! $workspace instanceof Workspace) {
            return;
        }

        $sourceAccountId = $this->input('account_id');
        $destinationAccountId = $this->input('destination_account_id');
        $feeAmount = $this->integer('fee_amount');
        $feeCategoryId = $this->input('fee_category_id');

        if (is_numeric($sourceAccountId) && is_numeric($destinationAccountId)) {
            if ((int) $sourceAccountId === (int) $destinationAccountId) {
                $validator->errors()->add('destination_account_id', 'The destination account must be different from the source account.');
            }

            $accounts = Account::query()
                ->where('workspace_id', $workspace->id)
                ->whereKey([(int) $sourceAccountId, (int) $destinationAccountId])
                ->get();

            if ($accounts->count() === 2 && $accounts->pluck('currency_code')->unique()->count() !== 1) {
                $validator->errors()->add('destination_account_id', 'Transfers between different currencies are not supported.');
            }
        }

        if ($feeAmount > 0 && ! is_numeric($feeCategoryId)) {
            $validator->errors()->add('fee_category_id', 'An expense category is required when a transfer fee is entered.');

            return;
        }

        if (! is_numeric($feeCategoryId)) {
            return;
        }

        $feeCategory = Category::query()
            ->where('workspace_id', $workspace->id)
            ->find($feeCategoryId);

        if ($feeCategory instanceof Category && $feeCategory->getRawOriginal('type') !== CategoryType::Expense->value) {
            $validator->errors()->add('fee_category_id', 'The transfer fee category must be an expense category.');
        }
    }
}
