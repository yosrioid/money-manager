<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('account'));
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
            'account_group_id' => [
                'nullable',
                'integer',
                Rule::exists('account_groups', 'id')->where('workspace_id', $workspace->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_favorite' => ['sometimes', 'boolean'],
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
                $account = $this->route('account');

                if (! $account instanceof Account) {
                    return;
                }

                if ($account->currency_code !== $this->input('currency_code') && $account->ledgerEntries()->exists()) {
                    $validator->errors()->add('currency_code', __('The currency cannot change after ledger entries are posted.'));
                }
            },
        ];
    }
}
