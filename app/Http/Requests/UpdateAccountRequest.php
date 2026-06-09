<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'currency_code' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code')],
            'opening_balance' => ['required', 'integer'],
            'account_group_id' => ['nullable', 'integer', Rule::exists('account_groups', 'id')],
            'description' => ['nullable', 'string', 'max:500'],
            'is_visible' => ['sometimes', 'boolean'],
            'include_in_total' => ['sometimes', 'boolean'],
        ];
    }
}
