<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:1000'],
            'creator_description' => ['nullable', 'string'],
            'completion_description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,in_progress,completed,cancelled'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
            'assigned_to' => ['nullable', 'string'],
            'department_id' => ['nullable', 'string'],
            'island_id' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'task_types' => ['nullable', 'array'],
            'attachment_url' => ['nullable', 'string'],
        ];
    }
}
