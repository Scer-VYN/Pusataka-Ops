<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->string('author');
            $table->string('publisher');
            $table->text('description')->nullable();
            $table->string('cover_theme')->default('focus');
            $table->unsignedInteger('total_stock')->default(0);
            $table->unsignedInteger('available_stock')->default(0);
            $table->unsignedInteger('popularity')->default(0);
            $table->timestamps();

            $table->index(['title', 'author', 'publisher']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
