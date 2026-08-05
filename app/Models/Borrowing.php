<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'book_id',
    'borrow_date',
    'due_date',
    'return_date',
    'status',
    'extended',
])]
class Borrowing extends Model
{
    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
            'extended' => 'boolean',
        ];
    }

    protected function isActive(): Attribute
    {
        return Attribute::get(fn (): bool => $this->return_date === null && in_array($this->status, ['borrowed', 'extended'], true));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
