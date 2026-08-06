<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080b0d">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LIBRARY // Command Center</title>
    @include('partials.preferences')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/theme.js', 'resources/js/app.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
    @endif
</head>
@php
    $coverMeta = [
        'focus' => ['code' => 'NF / 2026', 'label' => 'THE', 'title' => 'ART OF<br>FOCUS'],
        'signal' => ['code' => 'DATA / 12', 'label' => 'THE', 'title' => 'SIGNAL<br>&amp; NOISE'],
        'seeing' => ['code' => 'ART / 04', 'label' => 'WAYS OF', 'title' => 'SEEING'],
        'tomorrow' => ['code' => 'FIC / 09', 'label' => 'TOMORROW, AND', 'title' => 'TOMORROW,<br>AND TOMORROW'],
    ];
    $firstBook = $books->first();
    $firstCover = $firstBook
        ? ($coverMeta[$firstBook->cover_theme] ?? $coverMeta['focus'])
        : ['code' => 'OPS / 00', 'label' => 'NO', 'title' => 'BOOKS<br>AVAILABLE'];
    $heroAuthor = $firstBook?->author ?? 'CATALOGUE OFFLINE';
    $heroCategory = $firstBook?->category?->name ?? 'NO TITLES';
    $heroRack = $firstBook?->category?->rack ?? '--';
    $heroCoverTheme = $firstBook?->cover_theme ?? 'focus';
    $isLibrarian = $user->role === 'pustakawan';
@endphp
<body>
    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H19v15.5A2.5 2.5 0 0 0 16.5 16H5V5.5Z" stroke="currentColor" stroke-width="1.7"/>
                        <path d="M5 16v2.5A2.5 2.5 0 0 0 7.5 21H19M9 7h6M9 10h6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                </div>
                <div>
                    <strong>STACK<span>//</span>01</strong>
                    <small>LIBRARY SYSTEM</small>
                </div>
            </div>

            <div class="sidebar-label">MAIN MENU</div>
            <nav class="main-nav" aria-label="Main navigation">
                <a class="nav-item active" href="#command-center" data-nav="command-center" aria-current="page">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6v-9h-6v9Zm0-16v4h6V4h-6Z" stroke="currentColor" stroke-width="1.6"/></svg>
                    </span>
                    Command center
                    <span class="nav-arrow">↗</span>
                </a>
                <a class="nav-item" href="{{ route('collection.index') }}" data-nav="collection">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5A1.5 1.5 0 0 1 5.5 4H19v15.5A1.5 1.5 0 0 0 17.5 18H5.5A1.5 1.5 0 0 1 4 16.5v-11Z" stroke="currentColor" stroke-width="1.6"/><path d="M8 8h7M8 11h7M8 14h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    </span>
                    Explore collection
                    <span class="nav-count">{{ number_format($catalogSize) }}</span>
                </a>
                <a class="nav-item" href="{{ route('borrowings.index') }}" data-nav="borrowings">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M5 4.5h12a2 2 0 0 1 2 2V20H7a2 2 0 0 1-2-2V4.5Z" stroke="currentColor" stroke-width="1.6"/><path d="M5 17.5A2.5 2.5 0 0 1 7.5 15H19M9 8h6M9 11h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    </span>
                    My borrowings
                    <span class="nav-count nav-count-alert">{{ str_pad($activeBorrowingsCount, 2, '0', STR_PAD_LEFT) }}</span>
                </a>
                <a class="nav-item" href="#notifications" data-nav="notifications">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    Notifications
                    @if ($unreadNotificationsCount > 0)<span class="notification-dot"></span>@endif
                </a>
            </nav>

            <div class="sidebar-label sidebar-label-spaced">YOUR SPACE</div>
            <nav class="main-nav" aria-label="Personal navigation">
                <a class="nav-item" href="{{ route('collection.index', ['saved' => 1]) }}" data-nav="saved">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="m12 19-7.2 3 1.4-7.6L1 9.1l7.7-1.1L12 1l3.3 7 7.7 1.1-5.2 5.3 1.4 7.6-7.2-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
                    </span>
                    Saved titles
                    <span class="nav-count">{{ $savedBooksCount }}</span>
                </a>
                <a class="nav-item" href="{{ route('account.index') }}" data-nav="account">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4Z" stroke="currentColor" stroke-width="1.6"/><path d="m19.4 15 .1.1a1.8 1.8 0 0 1-2.5 2.5l-.1-.1a1.8 1.8 0 0 0-3.1 1.3v.2a1.8 1.8 0 0 1-3.6 0v-.2a1.8 1.8 0 0 0-3.1-1.3l-.1.1a1.8 1.8 0 1 1-2.5-2.5l.1-.1a1.8 1.8 0 0 0-1.3-3.1h-.2a1.8 1.8 0 0 1 0-3.6h.2a1.8 1.8 0 0 0 1.3-3.1l-.1-.1a1.8 1.8 0 1 1 2.5-2.5l.1.1a1.8 1.8 0 0 0 3.1-1.3V3a1.8 1.8 0 0 1 3.6 0v.2a1.8 1.8 0 0 0 3.1 1.3l.1-.1a1.8 1.8 0 1 1 2.5 2.5l-.1.1a1.8 1.8 0 0 0 1.3 3.1h.2a1.8 1.8 0 0 1 0 3.6h-.2a1.8 1.8 0 0 0-1.3 1.3Z" stroke="currentColor" stroke-width="1.3"/></svg>
                    </span>
                    04 / ACCOUNT
                </a>
                @if ($isLibrarian)
                    <a class="nav-item" href="{{ route('librarian.index') }}" data-nav="librarian">
                        <span class="nav-icon">⚙</span>
                        Library operations
                    </a>
                @endif
            </nav>

            <div class="sidebar-footer">
                <div class="system-status"><span class="pulse"></span><span>SYSTEMS NOMINAL</span><span class="status-code">v.2.4</span></div>
                <div class="profile">
                    <div class="avatar avatar-orange">{{ $initials }}</div>
                    <div class="profile-meta"><strong>{{ $user->name }}</strong><span>Member // {{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span></div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="more-button" type="submit" aria-label="Sign out">↪</button></form>
                </div>
            </div>
        </aside>
        <button class="sidebar-backdrop" id="sidebar-backdrop" type="button" aria-label="Close navigation"></button>

        <main class="main-content" aria-labelledby="dashboard-title">
            <header class="topbar">
                <button class="mobile-menu" id="mobile-menu" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="sidebar">
                    <span></span><span></span><span></span>
                </button>
                <x-breadcrumb current="COMMAND CENTER" home-label="HOME" :home-url="route('dashboard')" />
                <div class="topbar-actions">
                    <div class="global-search">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <input type="search" id="global-search" placeholder="Search titles, authors, publishers..." autocomplete="off">
                        <kbd>⌘ K</kbd>
                    </div>
                    <button class="icon-button notification-trigger" id="notification-trigger" type="button" aria-label="Show notifications" aria-expanded="false" aria-controls="notification-popover">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @if ($unreadNotificationsCount > 0)<span class="icon-alert"></span>@endif
                    </button>
                    <div class="topbar-divider"></div>
                    <button class="top-profile" id="profile-trigger" type="button" aria-label="Open profile" aria-expanded="false" aria-controls="profile-menu">
                        <div class="avatar avatar-orange">{{ $initials }}</div>
                        <div class="top-profile-copy"><strong>{{ $user->name }}</strong><span>{{ ucfirst($user->role) }}</span></div>
                        <svg viewBox="0 0 24 24" fill="none"><path d="m7 9 5 5 5-5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="profile-menu" id="profile-menu" hidden>
                        <div class="profile-menu-heading"><span>PROFILE // {{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</span><strong>{{ $user->name }}</strong></div>
                        <p>{{ $user->email }}</p>
                        <a href="{{ route('account.index') }}#profile" data-profile-link>OPEN PROFILE <span>↗</span></a>
                        <a href="{{ route('account.index') }}#settings" data-profile-link>OPEN SETTINGS <span>↗</span></a>
                    </div>
                </div>
            </header>

            <div class="content-wrap" id="command-center">
                <section class="welcome-row">
                    <div>
                        <p class="eyebrow"><span class="eyebrow-line"></span>{{ strtoupper($today->format('l, d M Y')) }} <span class="eyebrow-muted">//</span> {{ now()->format('H:i:s') }} {{ config('app.timezone') }} <span class="role-mode">{{ $isLibrarian ? 'OPERATIONS MODE' : 'MEMBER MODE' }}</span></p>
                        <h1 id="dashboard-title">{{ $isLibrarian ? 'GOOD SHIFT,' : 'GOOD MORNING,' }}<br><em>{{ strtoupper($firstName) }}.</em></h1>
                        <p class="welcome-copy">{{ $isLibrarian ? 'Your library operations dashboard.' : 'Your personal library operations dashboard.' }}<br>{{ $isLibrarian ? 'Keep the collection mission-ready.' : 'Stay curious. Stay ahead.' }}</p>
                    </div>
                    <div class="signal-card">
                        <div class="signal-rings"><span></span><span></span><span></span><b>{{ number_format($catalogSize) }}</b></div>
                        <div class="signal-copy"><span>CATALOG SIGNAL</span><strong>{{ $catalogSize > 0 ? 'ONLINE' : 'EMPTY' }}</strong><small>{{ number_format($catalogSize) }} titles indexed</small></div>
                    </div>
                </section>

                <section class="hero-panel">
                    <div class="hero-grid"></div>
                    <div class="hero-glow"></div>
                    <div class="hero-copy">
                        <p class="hero-kicker"><span class="orange-dot"></span>FEATURED COLLECTION <span class="hero-kicker-line"></span>01 / {{ str_pad($books->count(), 2, '0', STR_PAD_LEFT) }}</p>
                        <h2>{{ strtoupper($firstCover['label']) }}<br><strong>{!! $firstCover['title'] !!}</strong></h2>
                        <p>{{ $heroAuthor }}<br>{{ $heroCategory }} // {{ $heroRack }}</p>
                        <a class="primary-button" href="{{ $isLibrarian ? route('librarian.index') : route('collection.index') }}"><span>{{ $isLibrarian ? 'Open operations' : 'Explore collection' }}</span><b>↗</b></a>
                    </div>
                    <div class="hero-cover cover-{{ $heroCoverTheme }}">
                        <div class="cover-top">FIELD NOTES <span>01</span></div>
                        <div class="cover-center"><small>{{ $firstCover['label'] }}</small><strong>{!! $firstCover['title'] !!}</strong><i></i><em>{{ strtoupper($heroAuthor) }}</em></div>
                        <div class="cover-bottom"><span>STACK // 01</span><span>{{ strtoupper($heroCategory) }}</span></div>
                    </div>
                    <div class="hero-controls"><button class="hero-control active"></button><button class="hero-control"></button><button class="hero-control"></button><span>SCROLL TO DISCOVER <b>↓</b></span></div>
                </section>

                <section class="stats-grid" aria-label="Library overview">
                    <div class="stat-card stat-highlight">
                        <div class="stat-top"><span class="stat-label">ACTIVE LOANS</span><span class="stat-icon">↗</span></div>
                        <strong class="stat-value">{{ str_pad($activeBorrowingsCount, 2, '0', STR_PAD_LEFT) }}</strong>
                        <div class="stat-foot"><span class="status-marker {{ $dueThisWeekCount ? 'orange' : 'green' }}"></span><span>{{ $dueThisWeekCount ? 'One deadline approaching' : 'All in good standing' }}</span></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top"><span class="stat-label">DUE THIS WEEK</span><span class="stat-icon">◷</span></div>
                        <strong class="stat-value">{{ str_pad($dueThisWeekCount, 2, '0', STR_PAD_LEFT) }}</strong>
                        <div class="stat-foot"><span class="status-marker {{ $dueThisWeekCount ? 'orange' : 'green' }}"></span><span>{{ $nextReturn ? 'Return by '.$nextReturn->due_date->format('d M') : 'No deadlines' }}</span></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-top"><span class="stat-label">SAVED TITLES</span><span class="stat-icon">✦</span></div>
                        <strong class="stat-value">{{ $savedBooksCount }}</strong>
                        <div class="stat-foot"><span class="status-marker muted"></span><span>{{ $savedBooksCount }} titles in your list</span></div>
                    </div>
                    <div class="stat-card stat-catalog">
                        <div class="stat-top"><span class="stat-label">CATALOG SIZE</span><span class="stat-icon">▦</span></div>
                        <strong class="stat-value">{{ number_format($catalogSize) }}</strong>
                        <div class="stat-foot"><span class="status-marker blue"></span><span>{{ $newBooksCount }} added this month</span></div>
                    </div>
                </section>

                <section class="dashboard-grid">
                    <div class="collection-section" id="collection">
                        <div class="section-heading">
                            <div><p class="section-index">01 / COLLECTION</p><h2>QUICK DEPLOYMENT</h2><p class="section-subtitle">Hand-picked titles ready for your next mission.</p></div>
                            <a href="{{ route('collection.index') }}" class="text-link">VIEW ALL TITLES <span>↗</span></a>
                        </div>
                        <div class="filter-row" role="tablist" aria-label="Collection filters">
                            <button class="filter-chip active" data-filter="all" role="tab">All titles <span>{{ $books->count() }}</span></button>
                            <button class="filter-chip" data-filter="recent" role="tab">Recently added</button>
                            <button class="filter-chip" data-filter="popular" role="tab">Popular</button>
                            <button class="filter-chip" data-filter="available" role="tab">Available now</button>
                        </div>
                        <div class="book-grid" id="book-grid">
                            @foreach ($books as $book)
                                @php
                                    $bookFilters = [];
                                    if ($book->created_at->gte($today->copy()->subDays(30))) {
                                        $bookFilters[] = 'recent';
                                    }
                                    if ($book->popularity >= 80) {
                                        $bookFilters[] = 'popular';
                                    }
                                    if ($book->available_stock > 0) {
                                        $bookFilters[] = 'available';
                                    }
                                    $bookCover = $coverMeta[$book->cover_theme] ?? $coverMeta['focus'];
                                    $isSaved = in_array($book->id, $savedBookIds, true);
                                @endphp
                                <article class="book-card" data-category="{{ implode(' ', $bookFilters) }}" data-title="{{ $book->title }}" data-author="{{ $book->author }}" data-publisher="{{ $book->publisher }}">
                                    <button class="bookmark-button {{ $isSaved ? 'saved' : '' }}" data-book-id="{{ $book->id }}" aria-label="Save {{ $book->title }}">{{ $isSaved ? '★' : '☆' }}</button>
                                    <div class="book-cover cover-{{ $book->cover_theme }}">
                                        <span class="cover-code">{{ $bookCover['code'] }}</span>
                                        <div><small>{{ $bookCover['label'] }}</small><strong>{!! $bookCover['title'] !!}</strong><i></i><em>{{ strtoupper($book->author) }}</em></div>
                                        <span class="cover-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                    <div class="book-info">
                                        <div><span class="book-category">{{ strtoupper($book->category->name) }}</span><span class="availability {{ $book->available_stock > 0 ? 'available' : 'borrowed' }}"><i></i>{{ $book->available_stock > 0 ? 'AVAILABLE' : 'ON LOAN' }}</span></div>
                                        <h3>{{ $book->title }}</h3>
                                        <p>{{ $book->author }}</p>
                                        <button class="details-link" data-book="{{ $book->id }}">View details <span>→</span></button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <x-empty-state
                            id="empty-state"
                            title="NO TITLES MATCH THIS SEARCH PROTOCOL."
                            message="Adjust your keywords or choose another filter."
                            :hidden="$books->isNotEmpty()"
                        />
                    </div>

                    <aside class="briefing-column">
                        <section class="briefing-card" id="borrowings">
                            <div class="briefing-heading"><div><p class="section-index">02 / BRIEFING</p><h2>NEXT RETURN</h2></div><a class="round-arrow" href="{{ route('borrowings.index') }}" aria-label="Open borrowings">↗</a></div>
                            @if ($nextReturn)
                                @php
                                    $nextCover = $coverMeta[$nextReturn->book->cover_theme] ?? $coverMeta['focus'];
                                @endphp
                                <div class="return-book">
                                    <div class="mini-cover cover-{{ $nextReturn->book->cover_theme }}"><div><small>{{ $nextCover['label'] }}</small><strong>{!! $nextCover['title'] !!}</strong></div></div>
                                    <div><span class="book-category">{{ strtoupper($nextReturn->book->category->name) }}</span><h3>{{ $nextReturn->book->title }}</h3><p>{{ $nextReturn->book->author }}</p></div>
                                </div>
                                <div class="return-date"><div><span>RETURN BY</span><strong>{{ $nextReturn->due_date->format('d M Y') }}</strong></div><div class="days-left"><strong>{{ str_pad($daysUntilReturn, 2, '0', STR_PAD_LEFT) }}</strong><span>DAYS LEFT</span></div></div>
                                <div class="progress-track"><span style="width: {{ $returnProgress }}%"></span></div>
                            @else
                                <x-empty-state
                                    title="NO ACTIVE RETURNS ASSIGNED."
                                    message="Your active borrowing queue is clear."
                                    action-label="VIEW BORROWING LOG"
                                    :action-url="route('borrowings.index')"
                                />
                            @endif
                            <button class="outline-button" id="extend-button" data-borrowing-id="{{ $nextReturn?->id }}" @disabled(!$nextReturn) aria-disabled="{{ $nextReturn ? 'false' : 'true' }}"><span>{{ $nextReturn ? 'Request extension' : 'No active borrowing' }}</span><span>→</span></button>
                        </section>
                        <section class="activity-card" id="notifications">
                            <div class="briefing-heading"><div><p class="section-index">03 / LOG</p><h2>RECENT ACTIVITY</h2></div><span class="live-label"><i></i>LIVE</span></div>
                            <div class="activity-list">
                                @forelse ($activities as $activity)
                                    <div class="activity-item"><span class="activity-icon {{ $activity['icon_class'] }}">{{ $activity['icon'] }}</span><div><p><strong>{{ $activity['action'] }}</strong> {{ $activity['title'] }}</p><span>{{ $activity['date'] }}</span></div><b class="activity-status {{ $activity['status_class'] }}">{{ $activity['status'] }}</b></div>
                                @empty
                                    <x-empty-state
                                        title="NO RECENT ACTIVITY."
                                        message="Saved titles and borrowing updates will appear here."
                                        action-label="OPEN ACTIVITY LOG"
                                        :action-url="route('borrowings.index')"
                                    />
                                @endforelse
                            </div>
                            <a href="{{ route('borrowings.index') }}" class="text-link activity-link">OPEN ACTIVITY LOG <span>↗</span></a>
                        </section>
                    </aside>
                </section>
            </div>
        </main>
    </div>

    <div class="toast" id="toast" role="status" aria-live="polite"><span class="toast-icon">✓</span><span id="toast-message">Action completed.</span></div>

    @if ($firstBook)
        <div class="modal-backdrop" id="book-modal" aria-hidden="true">
            <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
                <button class="modal-close" id="modal-close" aria-label="Close dialog">×</button>
                <div class="modal-cover cover-{{ $firstBook->cover_theme }}" id="modal-cover"><span class="modal-cover-title" id="modal-cover-title">{{ strtoupper($firstBook->title) }}</span></div>
                <div class="modal-body">
                    <p class="section-index" id="modal-category">{{ strtoupper($firstBook->category->name) }}</p>
                    <h2 id="modal-title">{{ $firstBook->title }}</h2>
                    <p class="modal-author" id="modal-author">{{ $firstBook->author }}</p>
                    <div class="modal-rule"></div>
                    <dl class="book-meta">
                        <div><dt>PUBLISHER</dt><dd id="modal-publisher">{{ $firstBook->publisher }}</dd></div>
                        <div><dt>LOCATION</dt><dd id="modal-location">Rack {{ $firstBook->category->rack }}</dd></div>
                        <div><dt>AVAILABILITY</dt><dd class="modal-available" id="modal-availability">{{ $firstBook->available_stock > 0 ? $firstBook->available_stock.' copies ready' : 'Currently on loan' }}</dd></div>
                    </dl>
                    <p class="modal-description">{{ $firstBook->description }}</p>
                    <div class="modal-borrow-fields">
                        <label for="modal-borrow-date">START DATE<input id="modal-borrow-date" type="date" value="{{ now()->format('Y-m-d') }}" min="{{ now()->format('Y-m-d') }}" aria-describedby="modal-borrow-error"></label>
                        <label for="modal-borrow-duration">DURATION<select id="modal-borrow-duration" aria-describedby="modal-borrow-error"><option value="7">7 DAYS</option><option value="14" selected>14 DAYS</option><option value="21">21 DAYS</option><option value="30">30 DAYS</option></select></label>
                    </div>
                    <div class="borrow-summary" id="borrow-summary" aria-live="polite">
                        <span>MISSION SUMMARY</span>
                        <strong id="borrow-summary-title">{{ $firstBook->title }}</strong>
                        <p id="borrow-summary-dates">START {{ now()->format('Y-m-d') }} // DUE {{ now()->addDays(14)->format('Y-m-d') }}</p>
                    </div>
                    <div class="modal-form-error" id="modal-borrow-error" role="alert" hidden></div>
                    <a class="text-link modal-detail-link" id="modal-detail-link" href="{{ route('books.show', $firstBook) }}">OPEN FULL DETAILS <span>↗</span></a>
                    <button class="primary-button modal-borrow" id="modal-borrow" type="button" @disabled($firstBook->available_stock < 1) aria-disabled="{{ $firstBook->available_stock < 1 ? 'true' : 'false' }}"><span>{{ $firstBook->available_stock > 0 ? 'Request to borrow' : 'Currently unavailable' }}</span><b>→</b></button>
                </div>
            </div>
        </div>
    @endif

    <div class="notification-popover" id="notification-popover">
        <div class="popover-heading"><strong>NOTIFICATIONS</strong><button id="mark-read" type="button" @disabled($unreadNotificationsCount < 1) aria-disabled="{{ $unreadNotificationsCount < 1 ? 'true' : 'false' }}">MARK READ</button></div>
        @forelse ($notifications as $notification)
            <div class="popover-item"><span class="popover-dot {{ $notification->is_read ? 'muted-dot' : '' }}"></span><div><strong>{{ $notification->is_read ? 'Library update' : 'Return reminder' }}</strong><p>{{ $notification->message }}</p><small>{{ $notification->created_at->diffForHumans() }}</small></div></div>
        @empty
            <x-empty-state
                title="NO NOTIFICATIONS."
                message="Return reminders and library updates will appear here."
                action-label="OPEN BORROWING LOG"
                :action-url="route('borrowings.index')"
            />
        @endforelse
        <a class="popover-footer" href="{{ route('borrowings.index') }}">OPEN DUE BOOKS &amp; BORROWING LOG ↗</a>
    </div>
    <script>window.libraryBooks = @json($bookData);</script>
    @if (!file_exists(public_path('build/manifest.json')) && !file_exists(public_path('hot')))
        <script>{!! file_get_contents(resource_path('js/app.js')) !!}</script>
    @endif
</body>
</html>
