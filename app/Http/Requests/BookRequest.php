<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'cover_theme' => ['required', 'in:focus,signal,seeing,tomorrow'],
            'total_stock' => ['required', 'integer', 'min:0'],
            'available_stock' => ['required', 'integer', 'min:0'],
            'popularity' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                is_numeric($this->input('total_stock'))
                && is_numeric($this->input('available_stock'))
                && (int) $this->input('available_stock') > (int) $this->input('total_stock')
            ) {
                $validator->errors()->add(
                    'available_stock',
                    'Available stock cannot exceed total stock.',
                );
            }
        });
    }
}
