<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LibraryFlowTest extends TestCase
{
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        $member = User::query()->where('email', 'user@stack01.test')->firstOrFail();
        $nonFiction = Category::query()->create(['name' => 'Non-Fiction', 'rack' => 'B-04']);
        $history = Category::query()->create(['name' => 'History', 'rack' => 'A-01']);
        $focus = Book::query()->create([
            'category_id' => $nonFiction->id,
            'title' => 'The Art of Focus',
            'author' => 'Yuki Kawaguchi',
            'publisher' => 'Northline Press',
            'description' => 'A test catalogue title.',
            'cover_theme' => 'focus',
            'total_stock' => 4,
            'available_stock' => 3,
            'popularity' => 90,
        ]);
        $sapiens = Book::query()->create([
            'category_id' => $history->id,
            'title' => 'Sapiens',
            'author' => 'Yuval Noah Harari',
            'publisher' => 'Harper',
            'description' => 'A test history title.',
            'cover_theme' => 'signal',
            'total_stock' => 4,
            'available_stock' => 4,
            'popularity' => 80,
        ]);
        $activeBorrowing = Borrowing::query()->create([
            'user_id' => $member->id,
            'book_id' => $focus->id,
            'borrow_date' => now()->subDays(8),
            'due_date' => now()->addDays(6),
            'status' => 'borrowed',
        ]);
        Borrowing::query()->create([
            'user_id' => $member->id,
            'book_id' => $sapiens->id,
            'borrow_date' => now()->subDays(15),
            'due_date' => now()->subDays(2),
            'return_date' => now()->subDay(),
            'status' => 'returned',
        ]);
        Notification::query()->create([
            'user_id' => $member->id,
            'borrowing_id' => $activeBorrowing->id,
            'message' => 'The Art of Focus is due soon.',
            'is_read' => false,
        ]);
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_protected_pages_redirect_guests_to_login(): void
    {
        foreach ([
            route('account.index'),
            route('collection.index'),
            route('borrowings.index'),
            route('librarian.index'),
        ] as $protectedUrl) {
            $this->get($protectedUrl)->assertRedirect(route('login'));
        }
    }

    public function test_stateful_web_routes_use_the_web_middleware_group(): void
    {
        foreach (['sanctum.csrf-cookie', 'login', 'login.store', 'register', 'password.request', 'dashboard', 'account.index'] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] should be registered.");
            $this->assertContains('web', $route->gatherMiddleware(), "Route [{$routeName}] should use the web middleware group.");
        }
    }

    public function test_stateful_clients_can_get_a_csrf_cookie(): void
    {
        $this->get('/sanctum/csrf-cookie')
            ->assertNoContent()
            ->assertCookie('XSRF-TOKEN');
    }

    public function test_guests_can_open_the_password_recovery_form(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('RESET ACCESS')
            ->assertSee(route('login'), false)
            ->assertSee('/api/auth/forgot-password', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'), false)
            ->assertDontSee('Your session expired.');

        $this->get(route('login', ['session' => 'expired']))
            ->assertOk()
            ->assertSee('Your session expired.');
    }

    public function test_members_can_sign_in_from_the_login_page(): void
    {
        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'user@stack01.test',
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs(
            User::query()->where('email', 'user@stack01.test')->firstOrFail(),
        );
    }

    public function test_web_logout_ends_the_authenticated_session(): void
    {
        $member = User::query()->where('email', 'user@stack01.test')->firstOrFail();

        $this->actingAs($member)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_guests_can_open_the_new_password_form_from_a_reset_link(): void
    {
        $this->get(route('password.reset', [
            'token' => 'reset-token',
            'email' => 'user@stack01.test',
        ]))
            ->assertOk()
            ->assertSee('SET PASSWORD')
            ->assertSee('value="user@stack01.test"', false)
            ->assertSee('data-token="reset-token"', false)
            ->assertSee("'X-CSRF-TOKEN': csrfToken", false)
            ->assertSee('/api/auth/reset-password', false);
    }

    public function test_new_members_can_register(): void
    {
        $this->post(route('register.store'), [
            'name' => 'New Member',
            'email' => 'new-member@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-member@example.test',
            'role' => 'anggota',
        ]);
    }

    public function test_member_can_search_collection(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('collection.index', ['q' => 'focus']))
            ->assertOk()
            ->assertSee('The Art of Focus');
    }

    public function test_collection_filters_by_rack_and_availability(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('collection.index', ['rack' => 'A-01', 'available' => 1]))
            ->assertOk()
            ->assertSee('Sapiens')
            ->assertDontSee('The Art of Focus');
    }

    public function test_collection_navigation_exposes_primary_destinations(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('collection.index'))
            ->assertOk()
            ->assertSee(route('dashboard'), false)
            ->assertSee(route('borrowings.index'), false);
    }

    public function test_dashboard_settings_and_profile_options_are_navigable(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('account.index').'"', false)
            ->assertSee('href="'.route('account.index').'#profile"', false)
            ->assertSee('href="'.route('account.index').'#settings"', false)
            ->assertDontSee('id="settings"', false)
            ->assertDontSee('id="profile"', false)
            ->assertSee('id="profile-trigger"', false);
    }

    public function test_account_page_is_separate_from_dashboard_and_exposes_account_actions(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $member->update([
            'theme' => 'light',
            'notifications_enabled' => false,
        ]);

        $this->actingAs($member)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('04 / ACCOUNT')
            ->assertSee('aria-label="Breadcrumb"', false)
            ->assertSee('>ACCOUNT</strong>', false)
            ->assertSee('id="profile"', false)
            ->assertSee('class="avatar avatar-orange"', false)
            ->assertSee('id="profile-avatar"', false)
            ->assertSee('id="profile-avatar-preview"', false)
            ->assertSee('id="settings"', false)
            ->assertSee(route('account.profile.update'), false)
            ->assertSee(route('account.password.update'), false)
            ->assertSee('id="forgot-password-form"', false)
            ->assertSee('id="recovery-email"', false)
            ->assertSee('id="theme-toggle"', false)
            ->assertSee('id="notifications-toggle"', false)
            ->assertSee('"userId":"'.$member->id.'"', false)
            ->assertSee('"theme":"light"', false)
            ->assertSee('"notificationsEnabled":false', false);
    }

    public function test_member_can_upload_an_avatar_from_the_web_account_form(): void
    {
        Storage::fake('public');
        $member = User::query()->where('email', 'user@stack01.test')->firstOrFail();
        $member->update([
            'name' => 'Avatar Member',
            'email' => 'avatar-member@stack01.test',
        ]);

        $response = $this->actingAs($member)
            ->post(route('account.avatar.update'), [
                'avatar' => UploadedFile::fake()->image('account-avatar.webp'),
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('profile_success');

        $avatarPath = $member->refresh()->avatar;

        $this->assertIsString($avatarPath);
        Storage::disk('public')->assertExists($avatarPath);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'Avatar Member',
            'email' => 'avatar-member@stack01.test',
            'avatar' => $avatarPath,
        ]);
        $this->assertSame(route('account.index'), $response->headers->get('Location'));

        $this->actingAs($member)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($avatarPath), false)
            ->assertSee('name="avatar"', false)
            ->assertSee(route('account.profile.update'), false);
    }

    public function test_member_can_change_password_with_current_password_confirmation(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->from(route('account.index'))
            ->actingAs($member)
            ->put(route('account.password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $member->refresh()->password));

        $this->from(route('account.index'))
            ->actingAs($member)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('password_success');

        $this->assertTrue(Hash::check('new-password', $member->refresh()->password));
    }

    public function test_member_can_update_profile_data_for_the_authenticated_user(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->from(route('account.index'))
            ->actingAs($member)
            ->patch(route('account.profile.update'), [
                'name' => 'Updated Member',
                'email' => 'updated-member@stack01.test',
            ])
            ->assertRedirect(route('account.index'))
            ->assertSessionHas('profile_success');

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'Updated Member',
            'email' => 'updated-member@stack01.test',
            'role' => 'anggota',
        ]);
    }

    public function test_profile_output_escapes_special_characters(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $member->update(['name' => '<script>alert(1)</script>']);

        $this->actingAs($member)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_dashboard_book_list_exposes_full_detail_navigation(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->firstOrFail();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="modal-detail-link"', false)
            ->assertSee(route('books.show', $book), false);
    }

    public function test_member_can_borrow_from_book_detail(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->where('title', 'Sapiens')->firstOrFail();

        $this->actingAs($member)
            ->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('id="detail-borrow-form"', false);

        $this->actingAs($member)
            ->post(route('borrowings.store'), [
                'book_id' => $book->id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 14,
            ])
            ->assertRedirect(route('borrowings.index'));
    }

    public function test_api_authentication_returns_role_and_protects_member_routes(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();

        $login = $this->postJson('/api/auth/login', [
            'email' => $member->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.role', 'anggota');

        $this->withToken($login->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $member->id);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/borrowings')
            ->assertOk();

        $this->actingAs($librarian, 'sanctum')
            ->getJson('/api/borrowings')
            ->assertForbidden();
    }

    public function test_web_login_is_throttled_by_normalized_email_and_ip(): void
    {
        foreach (range(1, 5) as $_attempt) {
            $response = $this->post(route('login.store'), [
                'email' => 'USER@STACK01.TEST',
                'password' => 'wrong-password',
            ]);
            $this->assertSame(302, $response->getStatusCode());
        }

        $response = $this->post(route('login.store'), [
            'email' => 'user@stack01.test',
            'password' => 'wrong-password',
        ]);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertTrue($this->app['session.store']->has('login_error'));
    }

    public function test_api_login_is_throttled_by_normalized_email_and_ip_with_generic_failures(): void
    {
        foreach (range(1, 5) as $_attempt) {
            $this->postJson('/api/auth/login', [
                'email' => 'USER@STACK01.TEST',
                'password' => 'wrong-password',
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Invalid credentials.');
        }

        $this->postJson('/api/auth/login', [
            'email' => 'user@stack01.test',
            'password' => 'wrong-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many login attempts. Please try again later.');
    }

    public function test_api_logout_revokes_bearer_tokens(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $token = $member->createToken('logout-test');

        $this->withToken($token->plainTextToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->app['auth']->forgetGuards();

        $this->withToken($token->plainTextToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }

    public function test_api_logout_invalidates_browser_sessions(): void
    {
        $member = User::query()->where('email', 'user@stack01.test')->firstOrFail();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk();

        $this->withHeaders([
            'Origin' => 'http://localhost',
            'X-CSRF-TOKEN' => csrf_token(),
        ])
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->app['auth']->forgetGuards();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_api_clients_can_read_and_update_their_profile(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $member->update(['avatar' => 'avatars/member-avatar.png']);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('user.id', $member->id)
            ->assertJsonPath('user.email', $member->email)
            ->assertJsonPath('user.role', 'anggota')
            ->assertJsonPath('user.avatar', 'avatars/member-avatar.png')
            ->assertJsonPath('user.avatar_url', url('/storage/avatars/member-avatar.png'));

        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => 'API Profile Member',
                'email' => 'api-profile-member@stack01.test',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'API Profile Member')
            ->assertJsonPath('user.avatar', 'avatars/member-avatar.png');

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'API Profile Member',
            'email' => 'api-profile-member@stack01.test',
            'avatar' => 'avatars/member-avatar.png',
        ]);
    }

    public function test_profile_api_requires_authentication_and_validates_unique_email(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $otherMember = User::factory()->create(['role' => 'anggota']);

        $this->getJson('/api/profile')
            ->assertUnauthorized();

        $this->actingAs($member, 'sanctum')
            ->putJson('/api/profile', [
                'name' => '',
                'email' => $otherMember->email,
                'avatar' => str_repeat('a', 256),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'avatar']);
    }

    public function test_authenticated_api_clients_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $response = $this->actingAs($member, 'sanctum')
            ->post('/api/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('avatar.png'),
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Avatar uploaded successfully.');

        $avatarPath = $response->json('user.avatar');

        $this->assertIsString($avatarPath);
        $this->assertStringStartsWith('avatars/', $avatarPath);
        Storage::disk('public')->assertExists($avatarPath);
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'avatar' => $avatarPath,
        ]);
    }

    public function test_avatar_upload_replaces_only_the_users_previous_managed_avatar(): void
    {
        Storage::fake('public');
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $previousAvatar = UploadedFile::fake()->image('previous.png')->store('avatars', 'public');
        $member->update(['avatar' => $previousAvatar]);

        $response = $this->actingAs($member, 'sanctum')
            ->post('/api/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('replacement.webp'),
            ])
            ->assertOk();

        Storage::disk('public')->assertMissing($previousAvatar);
        Storage::disk('public')->assertExists($response->json('user.avatar'));
    }

    public function test_profile_updates_reject_client_supplied_avatar_paths(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/profile', [
                'name' => $member->name,
                'email' => $member->email,
                'avatar' => 'avatars/other-user.png',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    public function test_avatar_upload_does_not_delete_a_path_with_traversal_segments(): void
    {
        Storage::fake('public');
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $protectedAvatar = 'avatars/protected.png';
        $traversalPath = 'avatars/../avatars/protected.png';
        Storage::disk('public')->put($protectedAvatar, 'protected image');
        $member->update(['avatar' => $traversalPath]);

        $this->actingAs($member, 'sanctum')
            ->post('/api/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('replacement.png'),
            ])
            ->assertOk();

        Storage::disk('public')->assertExists($protectedAvatar);
    }

    public function test_avatar_upload_rejects_non_image_files_and_oversized_files(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->post('/api/profile/avatar', [
                'avatar' => UploadedFile::fake()->create('avatar.txt', 10, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');

        $this->actingAs($member, 'sanctum')
            ->post('/api/profile/avatar', [
                'avatar' => UploadedFile::fake()->image('large.png')->size(2049),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('avatar');
    }

    public function test_guests_cannot_upload_an_avatar(): void
    {
        $this->post('/api/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('avatar.png'),
        ])->assertUnauthorized();
    }

    public function test_authenticated_api_clients_can_change_their_password(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->putJson('/api/profile/password', [
                'current_password' => 'incorrect-password',
                'password' => 'new-api-password',
                'password_confirmation' => 'new-api-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password');

        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'new-api-password',
                'password_confirmation' => 'new-api-password',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated successfully.');

        $this->assertTrue(Hash::check('new-api-password', $member->refresh()->password));
    }

    public function test_changing_password_revokes_personal_access_tokens(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $token = $member->createToken('password-change-test');
        $tokenId = $token->accessToken->getKey();

        $this->withToken($token->plainTextToken)
            ->patchJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'new-api-password',
                'password_confirmation' => 'new-api-password',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
    }

    public function test_api_password_recovery_can_send_and_consume_a_reset_token(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->postJson('/api/auth/forgot-password', [
            'email' => $member->email,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If the email is registered, reset instructions will be sent.');

        $token = Password::broker()->createToken($member);

        $this->postJson('/api/auth/reset-password', [
            'email' => $member->email,
            'token' => $token,
            'password' => 'recovered-password',
            'password_confirmation' => 'recovered-password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Your password has been reset.');

        $this->assertTrue(Hash::check('recovered-password', $member->refresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $member->email]);
    }

    public function test_stateful_browser_can_consume_a_password_reset_token_with_csrf_protection(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $token = Password::broker()->createToken($member);

        $this->withHeaders([
            'Origin' => 'http://localhost',
            'X-CSRF-TOKEN' => csrf_token(),
        ])
            ->postJson('/api/auth/reset-password', [
                'email' => $member->email,
                'token' => $token,
                'password' => 'browser-recovered-password',
                'password_confirmation' => 'browser-recovered-password',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Your password has been reset.');
    }

    public function test_api_password_recovery_validates_email_and_reset_token(): void
    {
        $this->postJson('/api/auth/forgot-password', ['email' => 'missing@stack01.test'])
            ->assertOk()
            ->assertJsonPath('message', 'If the email is registered, reset instructions will be sent.');

        $this->postJson('/api/auth/reset-password', [
            'email' => 'user@stack01.test',
            'token' => 'invalid-token',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'missing@stack01.test',
            'token' => 'invalid-token',
            'password' => 'valid-password',
            'password_confirmation' => 'valid-password',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Unable to reset password.');
    }

    public function test_api_password_recovery_throttles_without_revealing_account_status(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/auth/forgot-password', [
                'email' => $member->email,
            ])
                ->assertOk()
                ->assertJsonPath('message', 'If the email is registered, reset instructions will be sent.');
        }
    }

    public function test_password_recovery_throttling_normalizes_email_keys(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/forgot-password', [
                'email' => 'USER@STACK01.TEST',
            ])->assertOk();
        }

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@stack01.test',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'If the email is registered, reset instructions will be sent.')
            ->assertHeader('Retry-After');
    }

    public function test_api_password_reset_returns_too_many_requests_when_throttled(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/auth/reset-password', [
                'email' => $member->email,
                'token' => 'invalid-token',
                'password' => 'valid-password',
                'password_confirmation' => 'valid-password',
            ])
                ->assertUnprocessable()
                ->assertJsonPath('message', 'Unable to reset password.');
        }

        $this->postJson('/api/auth/reset-password', [
            'email' => $member->email,
            'token' => 'invalid-token',
            'password' => 'valid-password',
            'password_confirmation' => 'valid-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('message', 'Too many password reset attempts. Try again later.')
            ->assertHeader('Retry-After');
    }

    public function test_authenticated_api_clients_can_read_and_save_preferences(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/preferences')
            ->assertOk()
            ->assertJsonPath('preferences.theme', 'dark')
            ->assertJsonPath('preferences.notifications_enabled', true);

        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/preferences', [
                'theme' => 'light',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Preferences updated successfully.')
            ->assertJsonPath('preferences.theme', 'light')
            ->assertJsonPath('preferences.notifications_enabled', true);

        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/preferences', [
                'notifications_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('preferences.theme', 'light')
            ->assertJsonPath('preferences.notifications_enabled', false);

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'theme' => 'light',
            'notifications_enabled' => false,
        ]);
    }

    public function test_browser_sessions_can_save_preferences_through_the_api(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->withHeaders([
                'Origin' => 'http://localhost',
                'X-CSRF-TOKEN' => csrf_token(),
            ])
            ->patchJson('/api/preferences', [
                'theme' => 'light',
            ])
            ->assertOk()
            ->assertJsonPath('preferences.theme', 'light');

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'theme' => 'light',
        ]);
    }

    public function test_preferences_api_rejects_invalid_values_and_guests(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->getJson('/api/preferences')
            ->assertUnauthorized();

        $this->actingAs($member, 'sanctum')
            ->putJson('/api/preferences', [
                'theme' => 'system',
                'notifications_enabled' => 'sometimes',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['theme', 'notifications_enabled']);
    }

    public function test_librarian_book_api_manages_catalogue_while_members_are_blocked(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();
        $category = Category::query()->firstOrFail();
        $payload = [
            'category_id' => $category->id,
            'title' => 'API Operations Manual',
            'author' => 'Library Ops',
            'publisher' => 'Stack Press',
            'description' => 'API catalogue management test.',
            'cover_theme' => 'focus',
            'total_stock' => 3,
            'available_stock' => 3,
            'popularity' => 15,
        ];

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/books', $payload)
            ->assertForbidden();

        $created = $this->actingAs($librarian, 'sanctum')
            ->postJson('/api/books', $payload)
            ->assertCreated()
            ->assertJsonPath('data.title', $payload['title']);

        $book = Book::query()->where('title', $payload['title'])->firstOrFail();

        $this->actingAs($librarian, 'sanctum')
            ->putJson("/api/books/{$book->id}", [...$payload, 'available_stock' => 2])
            ->assertOk()
            ->assertJsonPath('data.available_stock', 2);

        $this->actingAs($librarian, 'sanctum')
            ->deleteJson("/api/books/{$book->id}")
            ->assertOk();

        $this->assertDatabaseMissing('books', ['id' => $created->json('data.id')]);
    }

    public function test_librarian_stock_api_validates_and_updates_inventory(): void
    {
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();
        $book = Book::query()->firstOrFail();

        $this->actingAs($librarian, 'sanctum')
            ->patchJson("/api/books/{$book->id}/stock", [
                'total_stock' => 1,
                'available_stock' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('available_stock');

        $this->actingAs($librarian, 'sanctum')
            ->patchJson("/api/books/{$book->id}/stock", [
                'total_stock' => 6,
                'available_stock' => 5,
            ])
            ->assertOk()
            ->assertJsonPath('data.total_stock', 6)
            ->assertJsonPath('data.available_stock', 5);
    }

    public function test_stock_updates_cannot_reduce_total_below_active_borrowings(): void
    {
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();
        $book = Book::query()->where('title', 'The Art of Focus')->firstOrFail();
        $payload = [
            'category_id' => $book->category_id,
            'title' => $book->title,
            'author' => $book->author,
            'publisher' => $book->publisher,
            'description' => $book->description,
            'cover_theme' => $book->cover_theme,
            'total_stock' => 0,
            'available_stock' => 0,
            'popularity' => $book->popularity,
        ];

        $this->actingAs($librarian, 'sanctum')
            ->patchJson("/api/books/{$book->id}/stock", [
                'total_stock' => 2,
                'available_stock' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('available_stock');

        $this->actingAs($librarian, 'sanctum')
            ->patchJson("/api/books/{$book->id}/stock", [
                'total_stock' => 0,
                'available_stock' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_stock', 'available_stock']);

        $this->actingAs($librarian, 'sanctum')
            ->putJson("/api/books/{$book->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['total_stock', 'available_stock']);

        $this->actingAs($librarian)
            ->from(route('librarian.index'))
            ->put(route('librarian.books.update', $book), $payload)
            ->assertRedirect(route('librarian.index'))
            ->assertSessionHasErrors(['total_stock', 'available_stock']);
    }

    public function test_member_api_can_borrow_extend_and_return_a_book(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->where('title', 'Sapiens')->firstOrFail();

        $borrow = $this->actingAs($member, 'sanctum')
            ->postJson('/api/borrowings', [
                'book_id' => $book->id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 7,
            ])
            ->assertCreated()
            ->json('borrowing_id');

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/borrowings/{$borrow}/extend")
            ->assertOk();

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/borrowings/{$borrow}/return")
            ->assertOk();

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrow,
            'user_id' => $member->id,
            'status' => 'returned',
        ]);
    }

    public function test_web_extension_redirects_with_flash_and_updates_the_borrowing(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $borrowing = $member->borrowings()->where('status', 'borrowed')->firstOrFail();
        $dueDate = $borrowing->due_date->copy();

        $this->actingAs($member)
            ->from(route('borrowings.index'))
            ->post(route('borrowings.extend', $borrowing))
            ->assertRedirect(route('borrowings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'extended',
            'extended' => true,
        ]);
        $this->assertSame($dueDate->addDays(7)->toDateString(), $borrowing->refresh()->due_date->toDateString());
    }

    public function test_return_and_extension_revalidate_locked_borrowings(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $borrowing = $member->borrowings()->where('status', 'borrowed')->firstOrFail();
        $book = $borrowing->book()->firstOrFail();
        $availableBefore = $book->available_stock;

        $this->actingAs($member)
            ->postJson(route('borrowings.return', $borrowing))
            ->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_stock' => $availableBefore + 1,
        ]);

        $this->actingAs($member)
            ->postJson(route('borrowings.return', $borrowing))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('borrowing');

        $this->actingAs($member)
            ->postJson(route('borrowings.extend', $borrowing))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('borrowing');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_stock' => $availableBefore + 1,
        ]);
    }

    public function test_borrowing_api_history_and_detail_are_scoped_to_member(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $borrowing = $member->borrowings()->with('book.category')->firstOrFail();
        $otherMember = User::factory()->create(['role' => 'anggota']);
        $otherBorrowing = Borrowing::query()->create([
            'user_id' => $otherMember->id,
            'book_id' => $borrowing->book_id,
            'borrow_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'borrowed',
        ]);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/borrowings')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $member->id);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/borrowings/{$borrowing->id}")
            ->assertOk()
            ->assertJsonPath('id', $borrowing->id)
            ->assertJsonPath('book.id', $borrowing->book_id);

        $this->actingAs($member, 'sanctum')
            ->getJson("/api/borrowings/{$otherBorrowing->id}")
            ->assertForbidden();
    }

    public function test_notification_api_lists_and_scopes_due_reminders(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $notification = $member->libraryNotifications()->firstOrFail();
        $otherMember = User::factory()->create(['role' => 'anggota']);
        $otherNotification = Notification::query()->create([
            'user_id' => $otherMember->id,
            'message' => 'Private reminder.',
        ]);

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $member->id);

        $this->actingAs($member, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.is_read', true);

        $this->actingAs($member, 'sanctum')
            ->patchJson("/api/notifications/{$otherNotification->id}/read")
            ->assertForbidden();
    }

    public function test_dashboard_api_returns_role_specific_summary(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'anggota')
            ->assertJsonStructure(['data' => [
                'active_borrowings_count',
                'due_this_week_count',
                'saved_books_count',
                'unread_notifications_count',
            ]]);

        $this->actingAs($librarian, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'pustakawan')
            ->assertJsonStructure(['data' => [
                'catalog_size',
                'active_borrowings_count',
                'due_this_week_count',
                'recent_books',
            ]]);
    }

    public function test_backend_rejects_unavailable_and_duplicate_borrowings(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $activeBorrowing = $member->borrowings()
            ->whereNull('return_date')
            ->whereIn('status', ['borrowed', 'extended'])
            ->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/borrowings', [
                'book_id' => $activeBorrowing->book_id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 7,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('book_id');

        $unavailableBook = Book::query()->where('id', '<>', $activeBorrowing->book_id)->firstOrFail();
        $unavailableBook->update(['available_stock' => 0]);

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/borrowings', [
                'book_id' => $unavailableBook->id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 7,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('book_id');
    }

    public function test_api_errors_use_structured_json_payloads(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();

        $this->actingAs($member, 'sanctum')
            ->postJson('/api/borrowings', ['book_id' => 'invalid'])
            ->assertUnprocessable()
            ->assertJsonStructure(['message', 'errors' => ['book_id']]);

        $this->actingAs($librarian, 'sanctum')
            ->getJson('/api/borrowings')
            ->assertForbidden()
            ->assertJsonStructure(['message']);
    }

    public function test_book_api_includes_availability_status(): void
    {
        $book = Book::query()->firstOrFail();
        $book->update(['available_stock' => 0]);

        $this->getJson('/api/books?'.http_build_query(['q' => $book->title]))
            ->assertOk()
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.availability_status', 'unavailable')
            ->assertJsonPath('data.0.is_available', false);
    }

    public function test_book_detail_api_includes_category_and_availability(): void
    {
        $book = Book::query()->with('category')->firstOrFail();

        $this->getJson("/api/books/{$book->id}")
            ->assertOk()
            ->assertJsonPath('id', $book->id)
            ->assertJsonPath('category.id', $book->category->id)
            ->assertJsonPath('availability_status', 'available')
            ->assertJsonPath('is_available', true);
    }

    public function test_book_api_supports_search_filter_sort_and_status(): void
    {
        $book = Book::query()->with('category')->firstOrFail();
        $book->update(['available_stock' => 0]);

        $query = http_build_query([
            'q' => $book->title,
            'category' => $book->category_id,
            'rack' => $book->category->rack,
            'status' => 'unavailable',
            'sort' => 'title_asc',
        ]);

        $this->getJson("/api/books?{$query}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $book->id)
            ->assertJsonPath('data.0.availability_status', 'unavailable');
    }

    public function test_available_books_endpoint_excludes_titles_with_no_stock(): void
    {
        $unavailable = Book::query()->firstOrFail();
        $unavailable->update(['available_stock' => 0]);

        $this->getJson('/api/books/available')
            ->assertOk()
            ->assertJsonMissing(['id' => $unavailable->id]);
    }

    public function test_borrowing_request_endpoint_creates_a_pending_request(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->where('title', 'Sapiens')->firstOrFail();

        $response = $this->actingAs($member, 'sanctum')
            ->postJson('/api/borrowings/request', [
                'book_id' => $book->id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 14,
            ])
            ->assertStatus(202)
            ->assertJsonPath('borrowing.status', 'pending');

        $this->assertDatabaseHas('borrowings', [
            'id' => $response->json('borrowing.id'),
            'user_id' => $member->id,
            'status' => 'pending',
        ]);
    }

    public function test_borrowing_confirmation_generates_a_card_id_and_reserves_stock(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->where('title', 'Sapiens')->firstOrFail();
        $availableBefore = $book->available_stock;

        $pending = $this->actingAs($member, 'sanctum')
            ->postJson('/api/borrowings/request', [
                'book_id' => $book->id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 14,
            ])
            ->json('borrowing.id');

        $this->actingAs($member, 'sanctum')
            ->postJson("/api/borrowings/{$pending}/confirm")
            ->assertOk()
            ->assertJsonPath('borrowing.status', 'borrowed')
            ->assertJsonPath('card_id', sprintf('BR-%05d', $pending));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'available_stock' => $availableBefore - 1,
        ]);
    }

    public function test_catalog_filter_apis_include_categories_and_racks(): void
    {
        $category = Category::query()->orderBy('name')->firstOrFail();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.id', $category->id)
            ->assertJsonPath('data.0.rack', $category->rack)
            ->assertJsonPath('data.0.books_count', $category->books()->count());

        $racks = Category::query()
            ->select('rack')
            ->whereNotNull('rack')
            ->distinct()
            ->orderBy('rack')
            ->pluck('rack')
            ->values()
            ->all();

        $this->getJson('/api/racks')
            ->assertOk()
            ->assertJsonPath('data', $racks);
    }

    public function test_missing_book_uses_the_library_fallback_page(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('books.show', ['book' => 999999]))
            ->assertNotFound()
            ->assertSee('DATA')
            ->assertSee('NOT FOUND');
    }

    public function test_member_can_borrow_and_return_a_book(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->where('title', 'Sapiens')->firstOrFail();
        $availableBefore = $book->available_stock;

        $this->actingAs($member)
            ->postJson(route('borrowings.store'), [
                'book_id' => $book->id,
                'borrow_date' => now()->format('Y-m-d'),
                'duration' => 7,
            ])
            ->assertCreated();

        $borrowing = Borrowing::query()->where('user_id', $member->id)->where('book_id', $book->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('borrowings', ['id' => $borrowing->id, 'status' => 'borrowed']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'available_stock' => $availableBefore - 1]);

        $this->actingAs($member)
            ->post(route('borrowings.return', $borrowing))
            ->assertRedirect();

        $this->assertDatabaseHas('borrowings', ['id' => $borrowing->id, 'status' => 'returned']);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'available_stock' => $availableBefore]);
    }

    public function test_member_cannot_open_librarian_operations(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)->get(route('librarian.index'))->assertForbidden();
    }

    public function test_member_dashboard_hides_librarian_operations_link(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Library operations');
    }

    public function test_librarian_dashboard_shows_librarian_operations_link(): void
    {
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();

        $this->actingAs($librarian)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Library operations')
            ->assertSee('OPERATIONS MODE');
    }

    public function test_librarian_navigation_exposes_operations_from_library_pages(): void
    {
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();

        $this->actingAs($librarian)
            ->get(route('collection.index'))
            ->assertOk()
            ->assertSee('Library operations');

        $this->actingAs($librarian)
            ->get(route('borrowings.index'))
            ->assertOk()
            ->assertSee('LIBRARY OPERATIONS');
    }

    public function test_librarian_borrowing_watch_uses_database_records(): void
    {
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();

        $this->actingAs($librarian)
            ->get(route('librarian.index'))
            ->assertOk()
            ->assertSee('DATABASE // LIVE')
            ->assertSee('The Art of Focus')
            ->assertDontSee('MOCK FEED // API PENDING');
    }

    public function test_dashboard_disables_borrowing_for_an_unavailable_title(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();
        $book = Book::query()->latest()->firstOrFail();
        $book->update(['available_stock' => 0]);

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('id="modal-borrow" type="button" disabled', false);
    }

    public function test_librarian_can_add_a_book(): void
    {
        $librarian = User::query()->where('role', 'pustakawan')->firstOrFail();
        $category = Category::query()->firstOrFail();

        $this->actingAs($librarian)
            ->post(route('librarian.books.store'), [
                'category_id' => $category->id,
                'title' => 'Test Operations Manual',
                'author' => 'Library Ops',
                'publisher' => 'Stack Press',
                'description' => 'Testing catalogue management.',
                'cover_theme' => 'focus',
                'total_stock' => 2,
                'available_stock' => 2,
                'popularity' => 10,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('books', ['title' => 'Test Operations Manual']);
    }

    public function test_application_timezone_defaults_to_jakarta_and_is_displayed(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->assertSame('Asia/Jakarta', config('app.timezone'));

        $this->actingAs($member)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(config('app.timezone'), false)
            ->assertDontSee(' WIB', false);
    }

    public function test_populated_collection_renders_a_hidden_filter_empty_state(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->actingAs($member)
            ->get(route('collection.index'))
            ->assertOk()
            ->assertSee('id="catalog-empty-state"', false)
            ->assertSee('hidden', false);
    }

    public function test_due_date_command_does_not_duplicate_daily_reminders(): void
    {
        $borrowing = Borrowing::query()->where('status', 'borrowed')->firstOrFail();
        $borrowing->update(['due_date' => now()->addDays(2)]);

        $this->artisan('library:send-due-reminders')->assertExitCode(0);
        $this->artisan('library:send-due-reminders')->assertExitCode(0);

        $this->assertSame(
            1,
            Notification::query()->where('borrowing_id', $borrowing->id)->whereDate('created_at', now())->count(),
        );
    }
}
