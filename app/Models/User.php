<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'theme', 'notifications_enabled'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function libraryNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function savedBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'saved_books')->withTimestamps();
    }

    /**
     * Refresh the current session's marker after changing the password hash.
     *
     * AuthenticateSession rejects stateful sessions whose stored marker no longer matches the user.
     */
    public function invalidateOtherSessions(?string $exceptSessionId = null): void
    {
        if (! request()->hasSession()) {
            return;
        }

        $session = request()->session();

        if ($exceptSessionId !== null && $session->getId() !== $exceptSessionId) {
            return;
        }

        $guard = Auth::guard('web');

        if (! $guard instanceof SessionGuard || ! $guard->check() || (string) $guard->id() !== (string) $this->getKey()) {
            return;
        }

        $session->put(
            'password_hash_web',
            method_exists($guard, 'hashPasswordForCookie')
                ? $guard->hashPasswordForCookie($this->getAuthPassword())
                : $this->getAuthPassword(),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'notifications_enabled' => 'boolean',
        ];
    }
}
