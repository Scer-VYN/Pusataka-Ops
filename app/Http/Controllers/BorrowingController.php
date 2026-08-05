<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class BorrowingController extends Controller
{
    public function index(): View
    {
        return view('borrowings.index', [
            'borrowings' => Auth::user()->borrowings()->with(['book.category'])->latest('borrow_date')->paginate(10),
        ]);
    }
}
