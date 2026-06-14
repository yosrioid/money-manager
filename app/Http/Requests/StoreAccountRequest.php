<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Account::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->attributes->get('workspace');

        abort_unless($workspace instanceof Workspace, 403);

        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'currency_code' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code')],
            'credit_limit' => ['nullable', 'integer', 'min:0'],
            'statement_closing_day' => ['nullable', 'integer', 'between:1,28'],
            'payment_due_day' => ['nullable', 'integer', 'between:1,28'],
            'opening_balance' => ['required', 'integer'],
            'account_group_id' => [
                'nullable',
                'integer',
                Rule::exists('account_groups', 'id')->where('workspace_id', $workspace->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_visible' => ['sometimes', 'boolean'],
            'include_in_total' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = AccountType::tryFrom($this->string('type')->value());

                if ($type === AccountType::CreditCard && ! $this->filled('credit_limit')) {
                    $validator->errors()->add('credit_limit', __('Credit limit is required for credit card accounts.'));
                }

                if ($type !== AccountType::CreditCard && $this->filled('credit_limit')) {
                    $validator->errors()->add('credit_limit', __('Credit limit is only applicable to credit card accounts.'));
                }

                if ($type === AccountType::CreditCard && (! $this->filled('statement_closing_day') || ! $this->filled('payment_due_day'))) {
                    $validator->errors()->add('statement_closing_day', __('Statement closing and payment due days are required for credit card accounts.'));
                }

                if ($type !== AccountType::CreditCard && ($this->filled('statement_closing_day') || $this->filled('payment_due_day'))) {
                    $validator->errors()->add('statement_closing_day', __('Statement closing and payment due days are only applicable to credit card accounts.'));
                }
            },
        ];
    }
}
