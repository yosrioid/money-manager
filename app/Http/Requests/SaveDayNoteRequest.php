<?php

namespace App\Http\Requests;

use App\Models\DayNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveDayNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DayNote::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
