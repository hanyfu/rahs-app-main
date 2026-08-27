<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCallLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contact_name' => ['nullable', 'string'],
            'contact_phone' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'call_date' => ['nullable', 'date'],
            'attachment_url' => ['nullable', 'string'],
        ];
    }
}
