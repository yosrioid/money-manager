<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveOrderedResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $resource = $this->route('account_group')
            ?? $this->route('account')
            ?? $this->route('category')
            ?? $this->route('transaction_bookmark');

        return $resource instanceof Model
            && $this->user()->can('update', $resource);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'direction' => ['required', 'string', Rule::in(['up', 'down'])],
        ];
    }
}
