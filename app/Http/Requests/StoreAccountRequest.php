<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
}
