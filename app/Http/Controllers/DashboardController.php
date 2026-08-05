<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $today = Carbon::today();
        $activeBorrowings = $user->borrowings()
            ->with(['book.category'])
            ->whereIn('status', ['borrowed', 'extended'])
            ->whereNull('return_date')
            ->orderBy('due_date')
            ->get();
        $nextReturn = $activeBorrowings->first();
        $daysUntilReturn = $nextReturn
            ? max(0, $today->diffInDays($nextReturn->due_date, false))
            : null;
        $returnProgress = $nextReturn
            ? min(100, max(0, (int) round(
                $nextReturn->borrow_date->diffInDays($today)
                / max(1, $nextReturn->borrow_date->diffInDays($nextReturn->due_date))
                * 100,
            )))
            : 0;
        $books = Book::query()
            ->with('category')
            ->latest()
            ->limit(4)
            ->get();
        $savedBookIds = $user->savedBooks()->pluck('books.id')->all();
        $unreadNotificationsCount = $user->libraryNotifications()->where('is_read', false)->count();
        $notifications = $user->libraryNotifications()->latest()->limit(4)->get();
        $recentBorrowings = $user->borrowings()
            ->with('book')
            ->latest('updated_at')
            ->limit(3)
            ->get();
        $recentSaved = $user->savedBooks()->latest('saved_books.created_at')->with('category')->first();

        $activities = $recentBorrowings->map(fn ($borrowing): array => [
            'icon' => $borrowing->is_active ? '↓' : '✓',
            'icon_class' => $borrowing->is_active ? 'green-bg' : 'blue-bg',
            'action' => $borrowing->is_active ? 'Borrowed' : 'Returned',
            'title' => $borrowing->book->title,
            'date' => $borrowing->updated_at->diffForHumans(),
            'status' => $borrowing->is_active ? 'ACTIVE' : 'CLOSED',
            'status_class' => $borrowing->is_active ? '' : 'returned-status',
        ]);

        if ($recentSaved) {
            $activities->prepend([
                'icon' => '↗',
                'icon_class' => 'orange-bg',
                'action' => 'Saved',
                'title' => $recentSaved->title,
                'date' => $recentSaved->pivot->created_at->diffForHumans(),
                'status' => 'SAVED',
                'status_class' => 'saved-status',
            ]);
        }

        $bookData = $books->mapWithKeys(fn (Book $book): array => [
            (string) $book->id => [
                'title' => $book->title,
                'author' => $book->author,
                'publisher' => $book->publisher,
                'category' => strtoupper($book->category->name),
                'availability' => $book->available_stock > 0
                    ? "{$book->available_stock} copies ready"
                    : 'Currently on loan',
                'available' => $book->available_stock > 0,
                'cover' => "cover-{$book->cover_theme}",
                'description' => $book->description,
                'location' => "Rack {$book->category->rack}",
                'detail_url' => route('books.show', $book),
            ],
        ]);

        return view('welcome', [
            'user' => $user,
            'firstName' => explode(' ', trim($user->name))[0],
            'initials' => collect(explode(' ', trim($user->name)))
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => strtoupper($part[0]))
                ->implode(''),
            'books' => $books,
            'bookData' => $bookData,
            'savedBookIds' => $savedBookIds,
            'activeBorrowingsCount' => $activeBorrowings->count(),
            'dueThisWeekCount' => $activeBorrowings->filter(fn ($borrowing): bool => $borrowing->due_date->lte($today->copy()->addDays(7)))->count(),
            'savedBooksCount' => count($savedBookIds),
            'catalogSize' => Book::count(),
            'newBooksCount' => Book::where('created_at', '>=', $today->copy()->startOfMonth())->count(),
            'nextReturn' => $nextReturn,
            'daysUntilReturn' => $daysUntilReturn,
            'returnProgress' => $returnProgress,
            'notifications' => $notifications,
            'unreadNotificationsCount' => $unreadNotificationsCount,
            'activities' => $activities->take(3),
            'today' => $today,
        ]);
    }
}
