<?php

namespace App\Http\Requests;

use App\Models\AccountGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AccountGroup::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
