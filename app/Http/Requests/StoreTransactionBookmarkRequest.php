<?php

namespace App\Http\Requests;

use App\Models\TransactionBookmark;
use Illuminate\Contracts\Validation\ValidationRule;

class StoreTransactionBookmarkRequest extends StoreTransactionDraftRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TransactionBookmark::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            ...parent::rules(),
        ];
    }
}
