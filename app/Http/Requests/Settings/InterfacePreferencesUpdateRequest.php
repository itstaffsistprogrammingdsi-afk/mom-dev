<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InterfacePreferencesUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'required', Rule::in(['id', 'en'])],
            'appearance' => ['sometimes', 'required', Rule::in(['light', 'dark', 'system'])],
            'timezone' => ['sometimes', 'required', 'timezone'],
        ];
    }
}
