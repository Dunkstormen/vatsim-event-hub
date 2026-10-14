<?php

namespace App\Http\Requests;

use App\Enums\EventStatus;
use App\Enums\RecurrenceFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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
            'banner' => [
                'bail',
                'nullable',
                // PHP rejects oversized uploads (UPLOAD_ERR_INI_SIZE) before
                // Laravel's own validation runs, which otherwise surfaces as a
                // confusing generic failure rather than a clear size error.
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value instanceof UploadedFile && ! $value->isValid()) {
                        $fail('The banner image could not be uploaded. Please choose an image up to 5MB.');
                    }
                },
                'image',
                'max:5120',
            ],
        ];
    }
}
