<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'theme' => ['sometimes', 'required', 'string', 'in:dark,light'],
            'notifications_enabled' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
