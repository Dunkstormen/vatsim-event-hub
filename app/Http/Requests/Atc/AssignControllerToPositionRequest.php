<?php

namespace App\Http\Requests\Atc;

use Illuminate\Foundation\Http\FormRequest;

class AssignControllerToPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasPermissionTo('manage_atc_roster');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_cid' => ['required', 'integer', 'exists:users,cid'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
