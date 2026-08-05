<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrowings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('borrow_date');
            $table->date('due_date');
            $table->date('return_date')->nullable();
            $table->string('status')->default('borrowed');
            $table->boolean('extended')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['due_date', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrowings');
    }
};
