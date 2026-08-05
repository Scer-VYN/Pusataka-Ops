<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryActionController;
use App\Http\Controllers\LibrarianController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::patch('/account/profile', [AuthController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AuthController::class, 'updatePassword'])->name('account.password.update');
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
    Route::get('/books/{book}', [CollectionController::class, 'show'])->name('books.show');
    Route::get('/borrowings', [BorrowingController::class, 'index'])->name('borrowings.index');
    Route::post('/borrowings/{borrowing}/return', [LibraryActionController::class, 'returnBook'])->name('borrowings.return');
    Route::post('/borrowings', [LibraryActionController::class, 'borrow'])->name('borrowings.store');
    Route::post('/borrowings/{borrowing}/extend', [LibraryActionController::class, 'extend'])->name('borrowings.extend');
    Route::post('/books/{book}/saved', [LibraryActionController::class, 'toggleSaved'])->name('books.saved');
    Route::post('/notifications/read', [LibraryActionController::class, 'markNotificationsRead'])->name('notifications.read');
    Route::middleware('role:pustakawan')->prefix('librarian')->name('librarian.')->group(function (): void {
        Route::get('/', [LibrarianController::class, 'index'])->name('index');
        Route::post('/books', [LibrarianController::class, 'store'])->name('books.store');
        Route::put('/books/{book}', [LibrarianController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [LibrarianController::class, 'destroy'])->name('books.destroy');
    });
});
