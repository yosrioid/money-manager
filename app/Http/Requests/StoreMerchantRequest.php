<?php

namespace App\Http\Requests;

use App\Models\Merchant;
use App\Models\Workspace;
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
        $workspace = $this->attributes->get('workspace');

        abort_unless($workspace instanceof Workspace, 403);

        return [
            'name' => ['required', 'string', 'max:100'],
            'default_category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('workspace_id', $workspace->id),
            ],
        ];
    }
}
