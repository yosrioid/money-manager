<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Transaction::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $workspace = $this->attributes->get('workspace');

        abort_unless($workspace instanceof Workspace, 403);

        return [
            'type' => ['nullable', 'string', 'max:30'],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('workspace_id', $workspace->id)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'destination_account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('workspace_id', $workspace->id)],
            'fee_amount' => ['nullable', 'string', 'max:100'],
            'fee_category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'amount' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'merchant_id' => ['nullable', 'integer', Rule::exists('merchants', 'id')->where('workspace_id', $workspace->id)],
            'memo' => ['nullable', 'string', 'max:2000'],
            'tag_ids' => ['nullable', 'array', 'max:20'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')->where('workspace_id', $workspace->id)],
            'occurred_at' => ['nullable', 'date'],
            'splits' => ['nullable', 'array', 'max:50'],
            'splits.*' => ['array:category_id,amount'],
            'splits.*.category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('workspace_id', $workspace->id)],
            'splits.*.amount' => ['nullable', 'string', 'max:100'],
        ];
    }
}
