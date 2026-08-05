<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
            ->assertSee('href="#settings"', false)
            ->assertSee('href="#profile"', false)
            ->assertSee('id="settings"', false)
            ->assertSee('id="profile"', false)
            ->assertSee('id="profile-trigger"', false);
    }

    public function test_member_can_change_password_with_current_password_confirmation(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->from(route('dashboard'))
            ->actingAs($member)
            ->put(route('account.password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $member->refresh()->password));

        $this->from(route('dashboard'))
            ->actingAs($member)
            ->put(route('account.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('password_success');

        $this->assertTrue(Hash::check('new-password', $member->refresh()->password));
    }

    public function test_member_can_update_profile_data_for_the_authenticated_user(): void
    {
        $member = User::query()->where('role', 'anggota')->firstOrFail();

        $this->from(route('dashboard'))
            ->actingAs($member)
            ->patch(route('account.profile.update'), [
                'name' => 'Updated Member',
                'email' => 'updated-member@stack01.test',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('profile_success');

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'name' => 'Updated Member',
            'email' => 'updated-member@stack01.test',
            'role' => 'anggota',
        ]);
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
