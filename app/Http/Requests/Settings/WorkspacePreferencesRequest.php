<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkspacePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'default_currency' => ['required', 'string', 'size:3', Rule::exists('currencies', 'code')],
            'timezone' => ['required', 'string', 'timezone:all'],
            'locale' => ['required', 'string', Rule::in(['id', 'en'])],
            'number_format' => ['required', 'string', Rule::in(['id-ID', 'en-US', 'en-GB'])],
            'first_day_of_week' => ['required', 'integer', Rule::in([0, 1])],
            'month_start_day' => ['required', 'integer', 'min:1', 'max:28'],
            'adjust_month_for_weekend' => ['required', 'boolean'],
        ];
    }
}
