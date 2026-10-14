<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Enums\RecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'recurrence_frequency' => ['nullable', Rule::enum(RecurrenceFrequency::class)],
            'banner' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
