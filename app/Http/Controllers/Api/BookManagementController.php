<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\BookRequest;
use App\Http\Requests\StockRequest;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BookManagementController
{
    public function store(BookRequest $request): JsonResponse
    {
        $book = Book::query()->create($request->validated());

        return response()->json(['data' => $book->load('category')], 201);
    }

    public function update(BookRequest $request, Book $book): JsonResponse
    {
        $book->update($request->validated());

        return response()->json(['data' => $book->fresh()->load('category')]);
    }

    public function updateStock(StockRequest $request, Book $book): JsonResponse
    {
        $book->update($request->validated());

        return response()->json(['data' => $book->fresh()->load('category')]);
    }

    public function destroy(Book $book): JsonResponse
    {
        if ($book->borrowings()->whereNull('return_date')->exists()) {
            throw ValidationException::withMessages([
                'book' => 'A title with an active borrowing cannot be deleted.',
            ]);
        }

        $book->delete();

        return response()->json(['message' => 'Book deleted from the catalogue.']);
    }
}
