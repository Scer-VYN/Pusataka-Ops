<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        $sort = $request->string('sort')->toString();
        $allowedSorts = ['latest', 'popular', 'title_asc', 'title_desc'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'latest';

        $books = Book::query()
            ->with('category')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = $request->string('q')->trim()->toString();
                $query->where(function ($bookQuery) use ($term): void {
                    $bookQuery->where('title', 'like', "%{$term}%")
                        ->orWhere('author', 'like', "%{$term}%")
                        ->orWhere('publisher', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', $request->integer('category')))
            ->when($request->filled('rack'), function ($query) use ($request): void {
                $rack = $request->string('rack')->trim()->toString();
                $query->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('rack', $rack));
            })
            ->when($request->boolean('available'), fn ($query) => $query->where('available_stock', '>', 0))
            ->when($request->boolean('saved'), fn ($query) => $query->whereHas('savedBy', fn ($saved) => $saved->whereKey(Auth::id())))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->when($sort === 'popular', fn ($query) => $query->orderByDesc('popularity')->orderBy('title'))
            ->when($sort === 'title_asc', fn ($query) => $query->orderBy('title'))
            ->when($sort === 'title_desc', fn ($query) => $query->orderByDesc('title'))
            ->paginate(12)
            ->withQueryString();

        return view('collection.index', [
            'books' => $books,
            'categories' => Category::query()->orderBy('name')->get(),
            'filters' => $request->only(['q', 'category', 'rack', 'available', 'sort']),
        ]);
    }

    public function show(Book $book): View
    {
        return view('collection.show', ['book' => $book->load('category')]);
    }
}
