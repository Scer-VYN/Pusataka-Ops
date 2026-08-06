<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibrarianController extends Controller
{
    public function index(): View
    {
        return view('librarian.index', [
            'books' => Book::query()
                ->with('category')
                ->withCount([
                    'borrowings as active_borrowings_count' => fn ($query) => $query
                        ->whereNull('return_date')
                        ->whereIn('status', ['borrowed', 'extended']),
                ])
                ->latest()
                ->paginate(15),
            'categories' => Category::query()->orderBy('name')->get(),
            'activeBorrowings' => Borrowing::query()
                ->with(['book', 'user'])
                ->whereNull('return_date')
                ->whereIn('status', ['borrowed', 'extended'])
                ->latest('borrow_date')
                ->limit(4)
                ->get(),
        ]);
    }

    public function store(BookRequest $request): RedirectResponse
    {
        Book::query()->create($request->validated());

        return back()->with('success', 'Buku berhasil ditambahkan ke katalog.');
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        DB::transaction(function () use ($request, $book): void {
            $book = Book::query()->lockForUpdate()->findOrFail($book->id);
            $errors = $request->inventoryErrors(
                $book->borrowings()
                    ->whereNull('return_date')
                    ->whereIn('status', ['borrowed', 'extended'])
                    ->count(),
            );

            if ($errors !== []) {
                throw ValidationException::withMessages($errors);
            }

            $book->update($request->validated());
        });

        return back()->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->borrowings()->whereNull('return_date')->exists()) {
            throw ValidationException::withMessages(['book' => 'Buku yang sedang dipinjam tidak dapat dihapus.']);
        }

        $book->delete();

        return back()->with('success', 'Buku berhasil dihapus dari katalog.');
    }

}
