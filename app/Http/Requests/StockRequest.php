<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'total_stock' => ['required', 'integer', 'min:0'],
            'available_stock' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((int) $this->input('available_stock') > (int) $this->input('total_stock')) {
                $validator->errors()->add(
                    'available_stock',
                    'Available stock cannot exceed total stock.',
                );
            }
        });
    }
}
