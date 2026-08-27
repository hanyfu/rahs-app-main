<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_name' => ['required', 'string'],
            'contact_phone' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'call_date' => ['required', 'date'],
            'attachment_url' => ['nullable', 'string'],
        ];
    }
}
