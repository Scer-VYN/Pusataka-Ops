<?php

namespace App\Http\Controllers\Api;

use App\Models\Book;
use App\Models\Borrowing;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::today();

        if ($user->role === 'pustakawan') {
            $activeBorrowings = Borrowing::query()
                ->whereIn('status', ['borrowed', 'extended'])
                ->whereNull('return_date');

            return response()->json([
                'data' => [
                    'role' => $user->role,
                    'catalog_size' => Book::query()->count(),
                    'active_borrowings_count' => (clone $activeBorrowings)->count(),
                    'due_this_week_count' => (clone $activeBorrowings)
                        ->whereDate('due_date', '<=', $today->copy()->addDays(7))
                        ->count(),
                    'recent_books' => Book::query()->with('category')->latest()->limit(5)->get(),
                ],
            ]);
        }

        $activeBorrowings = $user->borrowings()
            ->with(['book.category'])
            ->whereIn('status', ['borrowed', 'extended'])
            ->whereNull('return_date')
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'data' => [
                'role' => $user->role,
                'active_borrowings_count' => $activeBorrowings->count(),
                'due_this_week_count' => $activeBorrowings
                    ->filter(fn (Borrowing $borrowing): bool => $borrowing->due_date->lte($today->copy()->addDays(7)))
                    ->count(),
                'saved_books_count' => $user->savedBooks()->count(),
                'unread_notifications_count' => $user->libraryNotifications()->where('is_read', false)->count(),
                'next_return' => $activeBorrowings->first(),
            ],
        ]);
    }
}
