<?php

namespace App\Http\Requests;

use App\Domain\Transactions\EvaluateAmountExpression;
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
            'category_id' => ['nullable', 'prohibited_if:type,transfer', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'destination_account_id' => ['required_if:type,transfer', 'prohibited_unless:type,transfer', 'integer', Rule::exists('accounts', 'id')->where('workspace_id', $workspace->id)],
            'fee_amount' => ['nullable', 'prohibited_unless:type,transfer'],
            'fee_category_id' => ['nullable', 'prohibited_unless:type,transfer', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'amount' => ['required'],
            'description' => ['required', 'string', 'max:255'],
            'merchant_id' => ['nullable', 'prohibited_if:type,transfer', 'integer', Rule::exists('merchants', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id)->whereNull('archived_at'))],
            'memo' => ['nullable', 'string', 'max:2000'],
            'tag_ids' => ['nullable', 'array', 'max:20'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists('tags', 'id')->where(fn ($query) => $query->where('workspace_id', $workspace->id)->whereNull('archived_at'))],
            'occurred_at' => ['required', 'date'],
            'splits' => ['nullable', 'prohibited_if:type,transfer', 'array', 'min:2', 'max:50'],
            'splits.*' => ['array:category_id,amount'],
            'splits.*.category_id' => ['required', 'integer', 'distinct', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'splits.*.amount' => ['required'],
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

            $evaluator = app(EvaluateAmountExpression::class);

            try {
                $amount = $evaluator->evaluate($this->input('amount'));
            } catch (\LogicException) {
                $validator->errors()->add('amount', 'The amount must be a safe arithmetic expression that resolves to a positive whole number.');

                return;
            }

            if ($type === TransactionType::Transfer->value) {
                $this->validateTransfer($validator, $evaluator);

                return;
            }

            $splits = $this->input('splits');

            if (is_array($splits) && $splits !== []) {
                $this->validateSplits($validator, $workspace, $type, $amount, $splits, $evaluator);

                return;
            }

            $categoryId = $this->input('category_id');

            if (! is_numeric($categoryId)) {
                $validator->errors()->add('category_id', 'A category is required when the transaction is not split.');

                return;
            }

            $category = Category::query()->where('workspace_id', $workspace->id)->find($categoryId);

            if (! $category instanceof Category) {
                return;
            }

            $expectedType = $type === TransactionType::Income->value ? CategoryType::Income : CategoryType::Expense;

            if ($category->getRawOriginal('type') !== $expectedType->value) {
                $validator->errors()->add('category_id', 'The selected category does not match the transaction type.');
            }
        }];
    }

    private function validateTransfer(Validator $validator, EvaluateAmountExpression $evaluator): void
    {
        $workspace = $this->attributes->get('workspace');

        if (! $workspace instanceof Workspace) {
            return;
        }

        $sourceAccountId = $this->input('account_id');
        $destinationAccountId = $this->input('destination_account_id');
        try {
            $feeAmount = filled($this->input('fee_amount')) ? $evaluator->evaluate($this->input('fee_amount')) : 0;
        } catch (\LogicException) {
            $validator->errors()->add('fee_amount', 'The fee must be a safe arithmetic expression that resolves to a positive whole number.');

            return;
        }
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

    /**
     * @param  array<int, mixed>  $splits
     */
    private function validateSplits(Validator $validator, Workspace $workspace, string $type, int $amount, array $splits, EvaluateAmountExpression $evaluator): void
    {
        $expectedType = $type === TransactionType::Income->value ? CategoryType::Income : CategoryType::Expense;
        $splitTotal = 0;

        foreach ($splits as $index => $split) {
            if (! is_array($split)) {
                continue;
            }

            try {
                $splitTotal += $evaluator->evaluate($split['amount'] ?? '');
            } catch (\LogicException) {
                $validator->errors()->add("splits.{$index}.amount", 'Each split amount must resolve to a positive whole number.');
            }

            $category = Category::query()->where('workspace_id', $workspace->id)->find($split['category_id'] ?? null);

            if ($category instanceof Category && $category->getRawOriginal('type') !== $expectedType->value) {
                $validator->errors()->add("splits.{$index}.category_id", 'Each split category must match the transaction type.');
            }
        }

        if ($splitTotal !== $amount) {
            $validator->errors()->add('splits', 'Split amounts must equal the transaction amount.');
        }
    }
}
