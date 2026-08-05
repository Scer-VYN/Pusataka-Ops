<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BorrowBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'borrow_date' => ['nullable', 'date', 'after_or_equal:today'],
            'duration' => ['nullable', 'integer', 'in:7,14,21,30'],
        ];
    }
}
