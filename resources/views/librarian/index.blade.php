<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STACK // Library Operations</title>
    @include('partials.preferences')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/theme.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
    @endif
    <style>
        .ops-shell{max-width:1340px;margin:0 auto;padding:30px 42px 70px}.ops-nav{display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-bottom:24px;border-bottom:1px solid var(--line-soft)}.ops-nav a,.ops-nav button{color:var(--muted);background:transparent;font:9px 'DM Mono',monospace}.ops-nav a:hover,.ops-nav button:hover{color:var(--orange-bright)}.ops-title{margin:50px 0 27px}.ops-title h1{font-size:48px}.ops-title p{margin-top:9px;color:var(--muted);font-size:11px}.loan-monitor{margin-bottom:22px;padding:17px;border:1px solid rgba(229,108,50,.25);background:linear-gradient(110deg,rgba(229,108,50,.08),var(--panel) 58%)}.loan-monitor header{display:flex;align-items:center;justify-content:space-between;margin-bottom:13px}.loan-monitor h2{color:var(--text);font:600 19px Rajdhani,sans-serif;letter-spacing:.08em}.loan-monitor header span{color:var(--orange);font:8px 'DM Mono',monospace}.loan-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.loan-row{display:grid;grid-template-columns:1fr auto;gap:12px;align-items:center;padding:12px;border:1px solid var(--line-soft);background:#0b1012}.loan-row strong,.loan-row small{display:block}.loan-row strong{margin-top:5px;color:#dce5df;font-size:11px}.loan-row small{margin-top:4px;color:var(--muted);font:8px 'DM Mono',monospace}.loan-label{color:#68787d;font:7px 'DM Mono',monospace;letter-spacing:.1em}.loan-status{text-align:right;color:var(--orange-bright);font:8px 'DM Mono',monospace}.loan-status b{display:block;margin-top:5px;color:#a9d674;font-weight:500}.loan-action{margin-top:8px;padding:5px 7px;color:#65747a;border:1px solid #29363b;background:transparent;font:7px 'DM Mono',monospace}.ops-layout{display:grid;grid-template-columns:315px 1fr;gap:22px;align-items:start}.ops-panel{padding:19px;border:1px solid var(--line-soft);background:var(--panel)}.ops-panel h2{color:var(--text);font:600 20px Rajdhani,sans-serif;letter-spacing:.08em}.ops-form{display:grid;gap:9px;margin-top:17px}.ops-form label{display:grid;gap:6px;color:var(--muted);font:8px 'DM Mono',monospace}.ops-form input,.ops-form select,.ops-form textarea{width:100%;padding:9px;border:1px solid var(--line);color:var(--text);background:#0b1012;font-size:10px;outline:0}.ops-form input[aria-invalid="true"],.ops-form select[aria-invalid="true"],.ops-form textarea[aria-invalid="true"],.ops-book form[data-book-form] input[aria-invalid="true"],.ops-book form[data-book-form] select[aria-invalid="true"]{border-color:#a24f38}.ops-form textarea{min-height:64px;resize:vertical}.ops-form button{padding:11px;border:0;color:#170d08;background:var(--orange-bright);font-size:10px;font-weight:800}.ops-form button:disabled{cursor:wait;opacity:.65}.form-error{margin-top:2px;color:#ff9470;font:9px 'DM Mono',monospace;line-height:1.5}.form-error[hidden]{display:none}.ops-book form[data-book-form] .form-error{flex-basis:100%}.ops-list{display:grid;gap:8px}.ops-book{display:grid;grid-template-columns:1fr auto;gap:14px;padding:15px;border:1px solid var(--line-soft);background:var(--panel)}.ops-book h3{color:var(--text);font-size:12px}.ops-book p{margin-top:4px;color:var(--muted);font:8px 'DM Mono',monospace}.ops-stock{display:inline-flex;align-items:center;gap:4px;margin-left:8px;color:var(--green)}.ops-stock::before{width:4px;height:4px;border-radius:50%;background:currentColor;content:''}.ops-stock.empty{color:var(--orange-bright)}.ops-book form{display:flex;gap:6px;align-items:center;margin-top:12px;flex-wrap:wrap}.ops-book input,.ops-book select{min-width:75px;padding:7px;border:1px solid var(--line);color:var(--text);background:#0b1012;font:9px 'DM Mono',monospace}.ops-book button{padding:8px;border:1px solid var(--line);color:#b6c1be;background:transparent;font:8px 'DM Mono',monospace}.ops-book button:disabled{cursor:wait;opacity:.65}.ops-book button:hover{border-color:var(--orange);color:var(--orange-bright)}.ops-book .delete-button{color:#d87d61;border-color:#713f32}.ops-book .delete-button:hover{color:#ff9470;border-color:#a24f38;background:rgba(113,63,50,.12)}.flash{margin-bottom:17px;padding:11px;color:#d4e6c4;border:1px solid #41533b;background:#162019;font:10px 'DM Mono',monospace}.errors{margin-bottom:17px;padding:11px;color:#ff9470;border:1px solid #713f32;background:#271612;font:10px 'DM Mono',monospace}.pagination{margin-top:20px;text-align:center}.pagination a,.pagination span{display:inline-block;padding:7px 10px;color:var(--muted);font:9px 'DM Mono',monospace}.pagination .active{color:#170d08;background:var(--orange-bright)}@media(max-width:900px){.ops-shell{padding:22px 18px}.ops-layout{grid-template-columns:1fr}.ops-title h1{font-size:39px}.loan-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <main class="ops-shell">
        <nav class="ops-nav"><a href="{{ route('dashboard') }}">← COMMAND CENTER</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">SIGN OUT ↪</button></form></nav>
        <header class="ops-title"><p class="section-index">OPS / LIBRARIAN ACCESS</p><h1>LIBRARY<br><em>OPERATIONS</em></h1><p>Maintain the collection, stock levels, and catalogue accuracy.</p></header>
        @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <section class="loan-monitor" aria-labelledby="loan-monitor-title">
            <header><h2 id="loan-monitor-title">BORROWING WATCH</h2><span>DATABASE // LIVE</span></header>
            <div class="loan-grid">
                @forelse($activeBorrowings as $borrowing)
                    @php
                        $daysUntilDue = (int) now()->startOfDay()->diffInDays($borrowing->due_date, false);
                        $dueLabel = $daysUntilDue < 0
                            ? 'OVERDUE BY '.abs($daysUntilDue).' DAYS'
                            : ($daysUntilDue === 0 ? 'DUE TODAY' : 'DUE IN '.$daysUntilDue.' DAYS');
                    @endphp
                    <article class="loan-row">
                        <div>
                            <span class="loan-label">LOAN // #{{ str_pad($borrowing->id, 5, '0', STR_PAD_LEFT) }}</span>
                            <strong>{{ $borrowing->book->title }}</strong>
                            <small>Member // {{ $borrowing->user->name }}</small>
                        </div>
                        <div class="loan-status">{{ $dueLabel }}<b>ON LOAN</b><button class="loan-action" type="button" disabled>MARK RETURNED</button></div>
                    </article>
                @empty
                    <x-empty-state
                        title="NO ACTIVE LOANS."
                        message="Borrowing activity will appear here when a title is checked out."
                    />
                @endforelse
            </div>
        </section>
        <div class="ops-layout">
            <section class="ops-panel">
                <h2>ADD NEW TITLE</h2>
                <form id="add-book-form" class="ops-form" method="POST" action="{{ route('librarian.books.store') }}" data-book-form novalidate>
                    @csrf
                    <label for="add-title">TITLE<input id="add-title" name="title" value="{{ old('title') }}" aria-describedby="add-book-error" required></label>
                    <label for="add-author">AUTHOR<input id="add-author" name="author" value="{{ old('author') }}" aria-describedby="add-book-error" required></label>
                    <label for="add-publisher">PUBLISHER<input id="add-publisher" name="publisher" value="{{ old('publisher') }}" aria-describedby="add-book-error" required></label>
                    <label for="add-category">CATEGORY<select id="add-category" name="category_id" aria-describedby="add-book-error" required>@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }} // {{ $category->rack }}</option>@endforeach</select></label>
                    <label for="add-cover-theme">COVER STYLE<select id="add-cover-theme" name="cover_theme" aria-describedby="add-book-error" required><option value="focus">Focus</option><option value="signal">Signal</option><option value="seeing">Seeing</option><option value="tomorrow">Tomorrow</option></select></label>
                    <label for="add-description">DESCRIPTION<textarea id="add-description" name="description" aria-describedby="add-book-error">{{ old('description') }}</textarea></label>
                    <label for="add-total-stock">TOTAL STOCK<input id="add-total-stock" name="total_stock" type="number" min="0" value="{{ old('total_stock', 1) }}" aria-describedby="add-book-error" required></label>
                    <label for="add-available-stock">AVAILABLE STOCK<input id="add-available-stock" name="available_stock" type="number" min="0" value="{{ old('available_stock', 1) }}" aria-describedby="add-book-error" required></label>
                    <label for="add-popularity">POPULARITY<input id="add-popularity" name="popularity" type="number" min="0" max="100" value="{{ old('popularity', 0) }}" aria-describedby="add-book-error" required></label>
                    <div id="add-book-error" class="form-error" data-form-error role="alert" hidden></div>
                    <button type="submit">ADD TO CATALOG ↗</button>
                </form>
            </section>
            <section>
                <div class="ops-list">
                    @forelse($books as $book)
                        <article class="ops-book">
                            <div><h3>{{ $book->title }}</h3><p>{{ $book->author }} · {{ $book->category->name }} // Rack {{ $book->category->rack }}<span class="ops-stock {{ $book->available_stock < 1 ? 'empty' : '' }}">{{ $book->available_stock }}/{{ $book->total_stock }} READY</span></p>
                                <form method="POST" action="{{ route('librarian.books.update', $book) }}" data-book-form novalidate>
                                    @csrf @method('PUT')
                                    <input name="title" value="{{ $book->title }}" aria-label="Title" aria-describedby="book-{{ $book->id }}-error">
                                    <input name="author" value="{{ $book->author }}" aria-label="Author" aria-describedby="book-{{ $book->id }}-error">
                                    <input name="publisher" value="{{ $book->publisher }}" aria-label="Publisher" aria-describedby="book-{{ $book->id }}-error">
                                    <select name="category_id" aria-label="Category" aria-describedby="book-{{ $book->id }}-error">@foreach($categories as $category)<option value="{{ $category->id }}" @selected($book->category_id === $category->id)>{{ $category->name }}</option>@endforeach</select>
                                    <select name="cover_theme" aria-label="Cover style" aria-describedby="book-{{ $book->id }}-error"><option value="focus" @selected($book->cover_theme === 'focus')>Focus</option><option value="signal" @selected($book->cover_theme === 'signal')>Signal</option><option value="seeing" @selected($book->cover_theme === 'seeing')>Seeing</option><option value="tomorrow" @selected($book->cover_theme === 'tomorrow')>Tomorrow</option></select>
                                    <input name="description" value="{{ $book->description }}" type="hidden">
                                    <input name="total_stock" type="number" min="0" value="{{ $book->total_stock }}" aria-label="Total stock" aria-describedby="book-{{ $book->id }}-error">
                                    <input name="available_stock" type="number" min="0" value="{{ $book->available_stock }}" aria-label="Available stock" aria-describedby="book-{{ $book->id }}-error">
                                    <input name="popularity" type="number" min="0" max="100" value="{{ $book->popularity }}" aria-label="Popularity" aria-describedby="book-{{ $book->id }}-error">
                                    <div id="book-{{ $book->id }}-error" class="form-error" data-form-error role="alert" hidden></div>
                                    <button type="submit">SAVE</button>
                                </form>
                            </div>
                            <form method="POST" action="{{ route('librarian.books.destroy', $book) }}" onsubmit="return confirm('Remove this title from the catalogue?')">@csrf @method('DELETE')<button class="delete-button" type="submit" @disabled($book->active_borrowings_count > 0) aria-disabled="{{ $book->active_borrowings_count > 0 ? 'true' : 'false' }}" title="{{ $book->active_borrowings_count > 0 ? 'Return all active copies before deleting this title.' : 'Delete title' }}">{{ $book->active_borrowings_count > 0 ? 'ON LOAN' : 'DELETE' }}</button></form>
                        </article>
                    @empty
                        <x-empty-state
                            title="NO BOOKS IN THE COLLECTION."
                            message="Add a new title to activate the catalogue."
                            action-label="ADD NEW TITLE"
                            action-url="#add-book-form"
                        />
                    @endforelse
                </div>
                @if($books->hasPages())<div class="pagination">{{ $books->links() }}</div>@endif
            </section>
        </div>
    </main>
    <script>
        (() => {
            const allowedThemes = ['focus', 'signal', 'seeing', 'tomorrow'];
            const fieldLabels = {
                title: 'Title',
                author: 'Author',
                publisher: 'Publisher',
                category_id: 'Category',
                cover_theme: 'Cover style',
                total_stock: 'Total stock',
                available_stock: 'Available stock',
                popularity: 'Popularity',
            };

            document.querySelectorAll('[data-book-form]').forEach((form) => {
                const field = (name) => form.elements.namedItem(name);
                const errorElement = form.querySelector('[data-form-error]');
                const submitButton = form.querySelector('button[type="submit"]');

                const validate = () => {
                    const errors = [];
                    const values = {
                        title: field('title').value.trim(),
                        author: field('author').value.trim(),
                        publisher: field('publisher').value.trim(),
                    };

                    ['title', 'author', 'publisher'].forEach((name) => {
                        const input = field(name);
                        let message = '';

                        if (!values[name]) {
                            message = `${fieldLabels[name]} is required.`;
                        } else if (values[name].length > 255) {
                            message = `${fieldLabels[name]} must be 255 characters or fewer.`;
                        }

                        input.setAttribute('aria-invalid', message ? 'true' : 'false');
                        if (message) errors.push(message);
                    });

                    const category = field('category_id');
                    const theme = field('cover_theme');
                    const totalStock = field('total_stock');
                    const availableStock = field('available_stock');
                    const popularity = field('popularity');

                    const requiredSelections = [
                        [category, 'Category'],
                        [theme, 'Cover style'],
                    ];
                    requiredSelections.forEach(([input, label]) => {
                        const message = !input.value ? `${label} is required.` : '';
                        input.setAttribute('aria-invalid', message ? 'true' : 'false');
                        if (message) errors.push(message);
                    });

                    if (theme.value && !allowedThemes.includes(theme.value)) {
                        theme.setAttribute('aria-invalid', 'true');
                        errors.push('Select a valid cover style.');
                    }

                    const stockFields = [
                        [totalStock, 'Total stock', 0],
                        [availableStock, 'Available stock', 0],
                        [popularity, 'Popularity', 0],
                    ];
                    const numbers = {};
                    stockFields.forEach(([input, label, minimum]) => {
                        const value = input.value.trim();
                        const number = Number(value);
                        let message = '';

                        if (!value) {
                            message = `${label} is required.`;
                        } else if (!Number.isInteger(number) || number < minimum) {
                            message = `${label} must be a whole number of ${minimum} or more.`;
                        } else if (label === 'Popularity' && number > 100) {
                            message = 'Popularity must be 100 or less.';
                        } else {
                            numbers[input.name] = number;
                        }

                        input.setAttribute('aria-invalid', message ? 'true' : 'false');
                        if (message) errors.push(message);
                    });

                    if (numbers.available_stock !== undefined
                        && numbers.total_stock !== undefined
                        && numbers.available_stock > numbers.total_stock) {
                        availableStock.setAttribute('aria-invalid', 'true');
                        errors.push('Available stock cannot exceed total stock.');
                    }

                    errorElement.textContent = errors.join(' ');
                    errorElement.hidden = errors.length === 0;
                    return errors.length === 0;
                };

                form.querySelectorAll('input, select, textarea').forEach((input) => {
                    input.addEventListener('input', () => {
                        if (input.getAttribute('aria-invalid') === 'true') validate();
                    });
                    input.addEventListener('blur', () => validate());
                    input.addEventListener('change', () => validate());
                });

                form.addEventListener('submit', (event) => {
                    if (!validate()) {
                        event.preventDefault();
                        form.querySelector('[aria-invalid="true"]')?.focus();
                        return;
                    }

                    submitButton.disabled = true;
                });
            });
        })();
    </script>
</body>
</html>
