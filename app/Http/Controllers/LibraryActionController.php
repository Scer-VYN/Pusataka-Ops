<?php

namespace App\Http\Controllers;

use App\Http\Requests\BorrowBookRequest;
use App\Models\Book;
use App\Models\Borrowing;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryActionController extends Controller
{
    public function requestBorrow(BorrowBookRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $borrowDate = Carbon::parse($data['borrow_date'] ?? Carbon::today());
        $duration = (int) ($data['duration'] ?? 14);

        $borrowing = DB::transaction(function () use ($data, $user, $borrowDate, $duration): Borrowing {
            $book = Book::query()->lockForUpdate()->findOrFail($data['book_id']);

            if ($book->available_stock < 1) {
                throw ValidationException::withMessages(['book_id' => 'This title is currently on loan.']);
            }

            if ($user->borrowings()
                ->where('book_id', $book->id)
                ->whereNull('return_date')
                ->whereIn('status', ['pending', 'borrowed', 'extended'])
                ->exists()) {
                throw ValidationException::withMessages(['book_id' => 'You already have a request for this title.']);
            }

            return $user->borrowings()->create([
                'book_id' => $book->id,
                'borrow_date' => $borrowDate,
                'due_date' => $borrowDate->copy()->addDays($duration),
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'message' => 'Borrowing request is pending approval.',
            'borrowing' => $borrowing->load('book.category'),
        ], 202);
    }

    public function confirmBorrow(Request $request, Borrowing $borrowing): JsonResponse
    {
        abort_unless($borrowing->user_id === $request->user()->id, 403);

        $borrowing = DB::transaction(function () use ($borrowing): Borrowing {
            $borrowing = Borrowing::query()->lockForUpdate()->findOrFail($borrowing->id);

            if ($borrowing->status !== 'pending') {
                throw ValidationException::withMessages([
                    'borrowing' => 'This borrowing request is no longer pending.',
                ]);
            }

            $book = $borrowing->book()->lockForUpdate()->firstOrFail();
            if ($book->available_stock < 1) {
                throw ValidationException::withMessages([
                    'borrowing' => 'This title is currently on loan.',
                ]);
            }

            $book->decrement('available_stock');
            $borrowing->update(['status' => 'borrowed']);

            return $borrowing;
        });

        $borrowing->load('book.category');

        return response()->json([
            'message' => 'Borrowing confirmed.',
            'card_id' => sprintf('BR-%05d', $borrowing->id),
            'borrowing' => $borrowing,
        ]);
    }

    public function borrow(BorrowBookRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $user = $request->user();
        $borrowDate = Carbon::parse($data['borrow_date'] ?? Carbon::today());
        $duration = (int) ($data['duration'] ?? 14);

        $borrowing = DB::transaction(function () use ($data, $user, $borrowDate, $duration): Borrowing {
            $book = Book::query()->lockForUpdate()->findOrFail($data['book_id']);

            if ($book->available_stock < 1) {
                throw ValidationException::withMessages(['book_id' => 'This title is currently on loan.']);
            }

            if ($user->borrowings()
                ->where('book_id', $book->id)
                ->whereNull('return_date')
                ->whereIn('status', ['borrowed', 'extended'])
                ->exists()) {
                throw ValidationException::withMessages(['book_id' => 'You already have this title checked out.']);
            }

            $book->decrement('available_stock');

            return $user->borrowings()->create([
                'book_id' => $book->id,
                'borrow_date' => $borrowDate,
                'due_date' => $borrowDate->copy()->addDays($duration),
                'status' => 'borrowed',
            ]);
        });

        $payload = [
            'message' => "Borrow request accepted for {$borrowing->book()->value('title')}.",
            'borrowing_id' => $borrowing->id,
            'card_id' => sprintf('BR-%05d', $borrowing->id),
            'borrowing' => $borrowing->load('book.category'),
        ];

        if (! $request->expectsJson()) {
            return redirect()->route('borrowings.index')->with('success', $payload['message']);
        }

        return response()->json($payload, 201);
    }

    public function toggleSaved(Request $request, Book $book): JsonResponse
    {
        $user = $request->user();
        $saved = $user->savedBooks()->whereKey($book->id)->exists();

        if ($saved) {
            $user->savedBooks()->detach($book->id);
        } else {
            $user->savedBooks()->attach($book->id);
        }

        return response()->json(['saved' => ! $saved]);
    }

    public function markNotificationsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->libraryNotifications()->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['message' => 'Notifications marked as read.']);
    }

    public function extend(Request $request, Borrowing $borrowing): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($borrowing->user_id !== $user->id) {
            abort(403);
        }

        DB::transaction(function () use ($borrowing): void {
            $borrowing = Borrowing::query()->lockForUpdate()->findOrFail($borrowing->id);

            if (! $borrowing->is_active || $borrowing->extended) {
                throw ValidationException::withMessages(['borrowing' => 'This borrowing cannot be extended.']);
            }

            $borrowing->update([
                'due_date' => $borrowing->due_date->copy()->addDays(7),
                'status' => 'extended',
                'extended' => true,
            ]);
        });

        $message = 'Extension request accepted.';
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json(['message' => $message]);
    }

    public function returnBook(Request $request, Borrowing $borrowing): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        abort_unless($borrowing->user_id === $user->id, 403);

        DB::transaction(function () use ($borrowing): void {
            $borrowing = Borrowing::query()->lockForUpdate()->findOrFail($borrowing->id);

            if (! $borrowing->is_active) {
                throw ValidationException::withMessages(['borrowing' => 'This borrowing has already been returned.']);
            }

            $book = Book::query()->lockForUpdate()->findOrFail($borrowing->book_id);
            $borrowing->update([
                'return_date' => Carbon::today(),
                'status' => 'returned',
            ]);
            $book->increment('available_stock');
        });

        $message = 'Book successfully marked as returned.';
        if (! $request->expectsJson()) {
            return back()->with('success', $message);
        }

        return response()->json(['message' => $message]);
    }
}
