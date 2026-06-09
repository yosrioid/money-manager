<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMerchantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Merchant::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'default_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
        ];
    }
}
