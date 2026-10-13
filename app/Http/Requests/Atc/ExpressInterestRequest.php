<?php

namespace App\Http\Requests\Atc;

use Illuminate\Foundation\Http\FormRequest;

class ExpressInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('book_atc_positions');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'availability' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
