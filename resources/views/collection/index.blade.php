<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STACK // Collection</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
    <style>
        .page-shell { max-width: 1280px; margin: 0 auto; padding: 30px 42px 70px; }
        .page-nav { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding-bottom:24px; border-bottom:1px solid var(--line-soft); }
        .page-brand { color:var(--orange-bright); font:700 18px Rajdhani,sans-serif; letter-spacing:.12em; }
        .page-links { display:flex; align-items:center; gap:18px; color:#829097; font:9px 'DM Mono',monospace; text-transform:uppercase; }
        .page-links a:hover { color:var(--orange-bright); }
        .page-links button { color:#829097; background:transparent; font:9px 'DM Mono',monospace; text-transform:uppercase; }
        .page-links button:hover { color:var(--orange-bright); }
        .page-title { display:flex; align-items:end; justify-content:space-between; gap:20px; margin:52px 0 27px; }
        .page-title h1 { font-size:48px; }
        .page-title p { margin-top:10px; color:var(--muted); font-size:11px; }
        .filter-panel { display:grid; grid-template-columns:minmax(220px,1fr) 165px 145px 165px auto auto; gap:9px; padding:14px; margin-bottom:26px; border:1px solid var(--line-soft); background:var(--panel); }
        .filter-panel input,.filter-panel select { min-width:0; padding:10px; border:1px solid var(--line); color:var(--text); background:#0b1012; font-size:10px; outline:0; }
        .filter-panel input:focus,.filter-panel select:focus { border-color:var(--orange); }
        .filter-panel label:not(.sr-only) { display:flex; align-items:center; gap:7px; padding:0 6px; color:var(--muted); font:9px 'DM Mono',monospace; }
        .filter-panel button { padding:0 15px; color:#160d08; background:var(--orange-bright); font-size:10px; font-weight:800; }
        .catalog-meta { display:flex; justify-content:space-between; margin-bottom:14px; color:var(--muted); font:9px 'DM Mono',monospace; }
        .catalog-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:24px 14px; }
        .catalog-card { min-width:0; }
        .catalog-card-link { display:block; height:100%; padding:9px; border:1px solid transparent; transition:border-color .2s, background .2s, transform .2s; }
        .catalog-card-link:hover { border-color:var(--line); background:rgba(255,255,255,.025); transform:translateY(-2px); }
        .catalog-card-link:focus-visible { outline:2px solid var(--orange-bright); outline-offset:3px; }
        .catalog-card .book-cover { height:250px; }
        .catalog-card h2 { overflow:hidden; margin:11px 0 4px; color:var(--text); font-size:13px; text-overflow:ellipsis; white-space:nowrap; }
        .catalog-card p { color:var(--muted); font:9px 'DM Mono',monospace; }
        .catalog-card .detail-line { display:flex; justify-content:space-between; gap:8px; margin-top:9px; color:#8f9e9e; font:8px 'DM Mono',monospace; }
        .catalog-card .stock-status { display:flex; align-items:center; gap:5px; max-width:100%; padding:4px 6px; color:var(--green); border:1px solid rgba(156,204,91,.22); background:rgba(156,204,91,.06); font-size:7px; white-space:nowrap; }
        .catalog-card .stock-status::before { width:5px; height:5px; border-radius:50%; background:currentColor; content:''; }
        .catalog-card .stock-status.unavailable { color:var(--orange-bright); border-color:rgba(229,108,50,.3); background:rgba(229,108,50,.08); }
        .stock-meter { display:flex; align-items:center; gap:8px; margin-top:10px; color:var(--faint); font:7px 'DM Mono',monospace; letter-spacing:.08em; }
        .stock-meter-track { flex:1; height:3px; overflow:hidden; background:#202a2f; }
        .stock-meter-fill { width:var(--stock-level); height:100%; background:var(--green); }
        .stock-meter-fill.low { background:var(--orange-bright); }
        .catalog-card .detail-action { display:flex; align-items:center; justify-content:space-between; margin-top:16px; padding-top:10px; border-top:1px solid var(--line-soft); color:#9eaaa8; font:500 8px 'DM Mono',monospace; letter-spacing:.08em; }
        .catalog-card .detail-action span { color:var(--orange-bright); font-size:14px; transition:transform .2s; }
        .catalog-card-link:hover .detail-action span { transform:translateX(3px); }
        .pagination { display:flex; justify-content:center; gap:6px; margin-top:34px; }
        .pagination a,.pagination span { padding:8px 11px; border:1px solid var(--line); color:var(--muted); font:9px 'DM Mono',monospace; }
        .pagination .active { color:#170d08; background:var(--orange-bright); }
        .flash { margin-bottom:18px; padding:11px 13px; color:#d4e6c4; border:1px solid #41533b; background:#162019; font:10px 'DM Mono',monospace; }
        .empty-state strong { display:block; color:#aab8b5; font:600 11px 'Rajdhani',sans-serif; letter-spacing:.08em; }
        .empty-state p { margin-top:8px; color:#68787d; }
        .empty-state a { display:inline-block; margin-top:13px; color:var(--orange-bright); }
        .empty-state a:hover { color:#ff9c63; }
        .filter-panel .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
        @media(max-width:850px){.page-shell{padding:24px 18px 50px}.filter-panel{grid-template-columns:1fr 1fr}.filter-panel input{grid-column:1/-1}.filter-panel button{min-height:34px}.catalog-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.catalog-meta{flex-wrap:wrap;gap:8px 16px}.page-title h1{font-size:38px}}
        @media(max-width:520px){.page-nav{align-items:flex-start;gap:16px}.page-links{flex-wrap:wrap;justify-content:flex-end;gap:8px}.page-title{display:block}.page-title .section-subtitle{display:block;margin-top:18px}.filter-panel{grid-template-columns:1fr}.catalog-grid{gap:22px 10px}.catalog-card .book-cover{height:220px}.catalog-card .detail-line{flex-wrap:wrap}}
        @media(max-width:420px){.catalog-grid{grid-template-columns:1fr}.catalog-card .book-cover{width:min(100%,260px);height:300px;margin:0 auto}}
    </style>
</head>
@php
    $coverMeta = [
        'focus' => ['code' => 'NF / 2026', 'label' => 'THE', 'title' => 'ART OF<br>FOCUS'],
        'signal' => ['code' => 'DATA / 12', 'label' => 'THE', 'title' => 'SIGNAL<br>&amp; NOISE'],
        'seeing' => ['code' => 'ART / 04', 'label' => 'WAYS OF', 'title' => 'SEEING'],
        'tomorrow' => ['code' => 'FIC / 09', 'label' => 'TOMORROW, AND', 'title' => 'TOMORROW,<br>AND TOMORROW'],
    ];
@endphp
<body class="collection-page">
    <main class="page-shell" aria-labelledby="collection-title">
        <nav class="page-nav">
            <a class="page-brand" href="{{ route('dashboard') }}">STACK<span style="color:#68777d">//01</span></a>
            <div class="page-links">
                <a href="{{ route('dashboard') }}">Command center</a>
                <a href="{{ route('borrowings.index') }}">My borrowings</a>
                @if (auth()->user()?->role === 'pustakawan')
                    <a href="{{ route('librarian.index') }}">Library operations</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
            </div>
        </nav>
        <header class="page-title">
            <div><p class="section-index">01 / COLLECTION DATABASE</p><h1 id="collection-title">EXPLORE<br><em>COLLECTION</em></h1><p>Search every title in the library inventory.</p></div>
            <span class="section-subtitle">{{ $books->total() }} matching titles</span>
        </header>
        @if (session('success'))<div class="flash">{{ session('success') }}</div>@endif
        <form id="collection-filter-form" class="filter-panel" method="GET" action="{{ route('collection.index') }}">
            <label class="sr-only" for="collection-search">Search the collection</label>
            <input id="collection-search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search title, author, publisher...">
            <label class="sr-only" for="collection-category">Filter by category</label>
            <select id="collection-category" name="category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(($filters['category'] ?? '') == $category->id)>{{ $category->name }} // Rack {{ $category->rack }}</option>@endforeach</select>
            <label class="sr-only" for="collection-rack">Filter by rack</label>
            <select id="collection-rack" name="rack"><option value="">All racks</option>@foreach($categories->pluck('rack')->unique()->sort() as $rack)<option value="{{ $rack }}" @selected(request('rack') === $rack)>Rack {{ $rack }}</option>@endforeach</select>
            <label class="sr-only" for="collection-sort">Sort the collection</label>
            <select id="collection-sort" name="sort"><option value="latest" @selected(($filters['sort'] ?? 'latest') === 'latest')>Newest first</option><option value="popular" @selected(($filters['sort'] ?? '') === 'popular')>Most popular</option><option value="title_asc" @selected(($filters['sort'] ?? '') === 'title_asc')>Title A-Z</option><option value="title_desc" @selected(($filters['sort'] ?? '') === 'title_desc')>Title Z-A</option></select>
            <label><input id="collection-available" type="checkbox" name="available" value="1" @checked(isset($filters['available']))> Available only</label>
            <button type="submit">SEARCH ↗</button>
        </form>
        <div class="catalog-meta"><span id="catalog-result-count" aria-live="polite">INVENTORY // {{ $books->firstItem() ?? 0 }}-{{ $books->lastItem() ?? 0 }}</span><span>{{ !empty($filters['q']) ? 'SEARCH // '.strtoupper($filters['q']) : 'FULL CATALOGUE' }}</span><span>STATUS REFRESHED {{ now()->format('d M Y H:i') }}</span></div>
        <section class="catalog-grid">
            @forelse($books as $book)
                @php($cover = $coverMeta[$book->cover_theme] ?? $coverMeta['focus'])
                @php($stockPercentage = $book->total_stock > 0 ? round(($book->available_stock / $book->total_stock) * 100) : 0)
                <article class="catalog-card" data-catalog-card data-category="{{ $book->category_id }}" data-rack="{{ $book->category->rack }}" data-available="{{ $book->available_stock > 0 ? '1' : '0' }}" data-title="{{ strtolower($book->title) }}" data-popularity="{{ $book->popularity }}" data-created-at="{{ $book->created_at->timestamp }}" data-search-text="{{ strtolower($book->title.' '.$book->author.' '.$book->publisher) }}">
                    <a class="catalog-card-link" href="{{ route('books.show', $book) }}" aria-label="View details for {{ $book->title }}">
                        <div class="book-cover cover-{{ $book->cover_theme }}"><span class="cover-code">{{ $cover['code'] }}</span><div><small>{{ $cover['label'] }}</small><strong>{!! $cover['title'] !!}</strong><i></i><em>{{ strtoupper($book->author) }}</em></div><span class="cover-number">{{ str_pad($book->id, 2, '0', STR_PAD_LEFT) }}</span></div>
                        <h2>{{ $book->title }}</h2>
                        <p>{{ $book->author }} // {{ $book->publisher }}</p>
                        <div class="detail-line"><span>RACK {{ $book->category->rack }}</span><span class="stock-status {{ $book->available_stock < 1 ? 'unavailable' : '' }}">{{ $book->available_stock }}/{{ $book->total_stock }} {{ $book->available_stock < 1 ? 'ON LOAN' : 'AVAILABLE' }}</span></div>
                        <div class="stock-meter" role="progressbar" aria-label="Available stock for {{ $book->title }}" aria-valuemin="0" aria-valuemax="{{ $book->total_stock }}" aria-valuenow="{{ $book->available_stock }}">
                            <span>STOCK SIGNAL</span>
                            <span class="stock-meter-track"><span class="stock-meter-fill {{ $stockPercentage < 25 ? 'low' : '' }}" style="--stock-level: {{ $stockPercentage }}%;"></span></span>
                            <span>{{ $stockPercentage }}%</span>
                        </div>
                        <div class="detail-action"><span>OPEN BOOK INTEL</span><span aria-hidden="true">↗</span></div>
                    </a>
                </article>
            @empty
                <x-empty-state
                    id="catalog-empty-state"
                    title="NO TITLES MATCH THIS SEARCH PROTOCOL."
                    message="Adjust your keywords or reset the active filters."
                    message-id="catalog-empty-detail"
                    :action-label="(!empty($filters['q']) || !empty($filters['category']) || request()->filled('rack') || !empty($filters['available'])) ? 'RESET FILTERS' : null"
                    :action-url="(!empty($filters['q']) || !empty($filters['category']) || request()->filled('rack') || !empty($filters['available'])) ? route('collection.index') : null"
                />
            @endforelse
        </section>
        @if($books->hasPages())<div class="pagination">{{ $books->links() }}</div>@endif
    </main>
    <script>
        (() => {
            const searchInput = document.querySelector('#collection-search');
            const categoryFilter = document.querySelector('#collection-category');
            const rackFilter = document.querySelector('#collection-rack');
            const sortFilter = document.querySelector('#collection-sort');
            const availableFilter = document.querySelector('#collection-available');
            const filterForm = document.querySelector('#collection-filter-form');
            const cards = [...document.querySelectorAll('[data-catalog-card]')];
            const emptyState = document.querySelector('#catalog-empty-state');
            const emptyDetail = document.querySelector('#catalog-empty-detail');
            const resultCount = document.querySelector('#catalog-result-count');

            if (!searchInput || !filterForm || cards.length === 0) {
                return;
            }

            const filterCards = () => {
                const term = searchInput.value.trim().toLowerCase();
                const category = categoryFilter?.value ?? '';
                const rack = rackFilter?.value ?? '';
                let visibleCount = 0;

                cards.forEach((card) => {
                    const matchesSearch = !term || card.dataset.searchText.includes(term);
                    const matchesCategory = !category || card.dataset.category === category;
                    const matchesRack = !rack || card.dataset.rack === rack;
                    const matchesAvailability = !availableFilter?.checked || card.dataset.available === '1';
                    const visible = matchesSearch && matchesCategory && matchesRack && matchesAvailability;
                    card.hidden = !visible;
                    if (visible) {
                        visibleCount += 1;
                    }
                });

                if (emptyState) {
                    emptyState.hidden = visibleCount > 0;
                }
                if (emptyDetail && visibleCount === 0) {
                    emptyDetail.textContent = term
                        ? `No title contains "${searchInput.value.trim()}".`
                        : 'Adjust your category, rack, or availability filters.';
                }
                if (resultCount) {
                    resultCount.textContent = `VISIBLE // ${visibleCount} TITLES`;
                }
            };

            searchInput.addEventListener('input', filterCards);
            [categoryFilter, rackFilter, sortFilter, availableFilter].forEach((filter) => {
                filter?.addEventListener('change', () => filterForm.requestSubmit());
            });
        })();
    </script>
</body>
</html>
