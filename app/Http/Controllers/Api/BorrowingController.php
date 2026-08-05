<?php

namespace App\Http\Controllers\Api;

use App\Models\Borrowing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BorrowingController
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->borrowings()->with(['book.category'])->latest('borrow_date')->paginate(20),
        );
    }

    public function show(Request $request, Borrowing $borrowing): JsonResponse
    {
        abort_unless($borrowing->user_id === $request->user()->id, 403);

        return response()->json($borrowing->load(['book.category']));
    }
}
