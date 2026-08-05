<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookController
{
    public function available(Request $request): JsonResponse
    {
        $request->merge([
            'available' => true,
            'status' => 'available',
        ]);

        return $this->index($request);
    }

    public function index(Request $request): JsonResponse
    {
        $sort = $request->string('sort')->toString();
        $status = $request->string('status')->trim()->toString();
        $status = in_array($status, ['available', 'unavailable'], true) ? $status : null;
        $rack = $request->string('rack')->trim()->toString();
        $query = Book::query()->with('category')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->trim()->toString();
                $query->where(fn ($books) => $books
                    ->where('title', 'like', "%{$term}%")
                    ->orWhere('author', 'like', "%{$term}%")
                    ->orWhere('publisher', 'like', "%{$term}%"));
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($rack !== '', function ($query) use ($rack): void {
                $query->whereHas('category', fn ($category) => $category->where('rack', $rack));
            })
            ->when($request->boolean('available') || $status === 'available', fn ($query) => $query->where('available_stock', '>', 0))
            ->when($status === 'unavailable', fn ($query) => $query->where('available_stock', 0));

        match ($sort) {
            'popular' => $query->orderByDesc('popularity')->orderBy('title'),
            'title_asc' => $query->orderBy('title'),
            'title_desc' => $query->orderByDesc('title'),
            default => $query->latest(),
        };

        return response()->json(
            $query->paginate(20)->through(fn (Book $book): array => $this->bookPayload($book)),
        );
    }

    public function show(Book $book): JsonResponse
    {
        return response()->json($this->bookPayload($book->load('category')));
    }

    private function bookPayload(Book $book): array
    {
        return [
            ...$book->toArray(),
            'availability_status' => $book->available_stock > 0 ? 'available' : 'unavailable',
            'is_available' => $book->available_stock > 0,
        ];
    }
}
