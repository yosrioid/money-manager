<?php

namespace App\Http\Requests\Settings;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkspaceExchangeRatesRequest extends FormRequest
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
        $workspace = $this->attributes->get('workspace');

        abort_unless($workspace instanceof Workspace, 403);

        return [
            'exchange_rates' => ['array'],
            'exchange_rates.*.currency_code' => [
                'required',
                'string',
                'size:3',
                'distinct',
                Rule::exists('currencies', 'code'),
                Rule::notIn([$workspace->default_currency]),
            ],
            'exchange_rates.*.rate_to_base' => ['nullable', 'regex:/^\d{1,13}(\.\d{1,10})?$/', 'gt:0'],
        ];
    }
}
