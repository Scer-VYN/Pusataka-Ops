<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\BookManagementController;
use App\Http\Controllers\Api\BorrowingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\LibraryActionController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/books', [BookController::class, 'index']);
Route::get('/books/available', [BookController::class, 'available']);
Route::get('/books/{book}', [BookController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/racks', [CategoryController::class, 'racks']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'show']);
});

Route::middleware(['auth:sanctum', 'role:anggota'])->group(function (): void {
    Route::get('/borrowings', [BorrowingController::class, 'index']);
    Route::get('/borrowings/{borrowing}', [BorrowingController::class, 'show']);
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/borrowings/request', [LibraryActionController::class, 'requestBorrow']);
    Route::post('/borrowings/{borrowing}/confirm', [LibraryActionController::class, 'confirmBorrow']);
    Route::post('/borrowings', [LibraryActionController::class, 'borrow']);
    Route::post('/borrowings/{borrowing}/extend', [LibraryActionController::class, 'extend']);
    Route::post('/borrowings/{borrowing}/return', [LibraryActionController::class, 'returnBook']);
    Route::post('/books/{book}/saved', [LibraryActionController::class, 'toggleSaved']);
    Route::post('/notifications/read', [LibraryActionController::class, 'markNotificationsRead']);
});

Route::middleware(['auth:sanctum', 'role:pustakawan'])->group(function (): void {
    Route::post('/books', [BookManagementController::class, 'store']);
    Route::put('/books/{book}', [BookManagementController::class, 'update']);
    Route::patch('/books/{book}/stock', [BookManagementController::class, 'updateStock']);
    Route::delete('/books/{book}', [BookManagementController::class, 'destroy']);
});
