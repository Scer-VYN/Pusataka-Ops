<?php

namespace App\Providers;

use App\Http\Controllers\Api\PasswordResetController;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPasswordNotification::createUrlUsing(static function ($notifiable, string $token): string {
            $path = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false);

            return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
        });

        $genericResponse = static function (Request $_request, array $headers): JsonResponse {
            return response()->json([
                'message' => PasswordResetController::GENERIC_MESSAGE,
            ], 200, $headers);
        };
        $resetResponse = static function (Request $_request, array $headers): JsonResponse {
            return response()->json([
                'message' => PasswordResetController::RESET_THROTTLED_MESSAGE,
            ], 429, $headers);
        };
        $loginResponse = static function (Request $request, array $headers) {
            $message = 'Too many login attempts. Please try again later.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 429, $headers);
            }

            return redirect()
                ->back()
                ->withInput($request->only('email'))
                ->with('login_error', $message)
                ->setStatusCode(429);
        };

        RateLimiter::for('login', static function (Request $request) use ($loginResponse): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)
                    ->by('login:email:'.$email.'|ip:'.$request->ip())
                    ->response($loginResponse),
            ];
        });

        RateLimiter::for('password-recovery', static function (Request $request) use ($genericResponse): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)
                    ->by('password-recovery:email:'.$email.'|ip:'.$request->ip())
                    ->response($genericResponse),
                Limit::perMinute(30)
                    ->by('password-recovery:ip:'.$request->ip())
                    ->response($genericResponse),
            ];
        });

        RateLimiter::for('password-reset', static function (Request $request) use ($resetResponse): array {
            $email = Str::lower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(5)
                    ->by('password-reset:email:'.$email.'|ip:'.$request->ip())
                    ->response($resetResponse),
                Limit::perMinute(30)
                    ->by('password-reset:ip:'.$request->ip())
                    ->response($resetResponse),
            ];
        });
    }
}
