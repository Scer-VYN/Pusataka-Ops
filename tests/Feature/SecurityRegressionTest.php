<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    protected bool $seed = true;

    public function test_password_reset_notifications_ignore_a_hostile_request_host(): void
    {
        config(['app.url' => 'https://library.example.test']);
        Notification::fake();

        $member = User::query()->where('email', 'user@stack01.test')->firstOrFail();

        $this->withHeader('Host', 'attacker.example.test')
            ->postJson('/api/auth/forgot-password', ['email' => $member->email])
            ->assertOk();

        Notification::assertSentTo(
            $member,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use ($member): bool {
                $url = $notification->toMail($member)->actionUrl;

                return str_starts_with($url, 'https://library.example.test/reset-password/')
                    && ! str_contains($url, 'attacker.example.test');
            },
        );
    }

    public function test_password_changes_keep_the_current_array_session_authenticated(): void
    {
        $this->post(route('login.store'), [
            'email' => 'user@stack01.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->put(route('account.password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('account.index'));

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_password_hash_markers_revoke_other_array_sessions(): void
    {
        config(['session.driver' => 'array']);

        $member = User::query()->where('email', 'user@stack01.test')->firstOrFail();
        $oldPasswordHash = $member->getAuthPassword();
        $session = app('session')->driver('array');
        $session->setId('other-session');
        $session->start();

        $guard = Auth::guard('web');
        $session->put(
            'password_hash_web',
            $guard->hashPasswordForCookie($oldPasswordHash),
        );

        $member->forceFill(['password' => Hash::make('new-password')])->save();
        $member = $member->fresh();
        $guard->setUser($member);

        $request = Request::create('/api/auth/me');
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => $member);

        $this->expectException(AuthenticationException::class);

        app(AuthenticateSession::class)->handle($request, fn () => response()->noContent());
    }
}
