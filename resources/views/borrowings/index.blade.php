<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STACK // My Borrowings</title>
    @include('partials.preferences')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/theme.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
    @endif
    <style>
        .history-shell{max-width:1100px;margin:0 auto;padding:30px 42px 70px}.history-nav{display:flex;justify-content:space-between;flex-wrap:wrap;gap:12px;padding-bottom:24px;border-bottom:1px solid var(--line-soft)}.history-nav a{color:var(--muted);font:9px 'DM Mono',monospace}.history-nav a:hover{color:var(--orange-bright)}.history-title{margin:52px 0 28px}.history-title h1{font-size:48px}.history-title p{margin-top:10px;color:var(--muted);font-size:11px}.flash{padding:11px;margin-bottom:18px;color:#d4e6c4;border:1px solid #41533b;background:#162019;font:10px 'DM Mono',monospace}.history-summary{display:flex;gap:28px;margin-bottom:16px;padding:13px 15px;border:1px solid var(--line-soft);background:#0e1417}.history-summary span,.history-label{color:#68787d;font:8px 'DM Mono',monospace;letter-spacing:.1em}.history-summary strong{display:block;margin-top:5px;color:var(--orange-bright);font:600 20px 'Rajdhani',sans-serif}.history-list{display:grid;gap:9px}.history-row{display:grid;grid-template-columns:58px 1fr auto auto;align-items:center;gap:17px;padding:15px;border:1px solid var(--line-soft);background:var(--panel)}.history-cover{width:48px;height:65px}.history-label{display:block;margin-bottom:6px;color:var(--orange)}.history-row h2{color:var(--text);font-size:13px}.history-row p{margin-top:5px;color:var(--muted);font:9px 'DM Mono',monospace}.history-dates{color:#a8b4b2;font:9px 'DM Mono',monospace;line-height:1.8}.history-status{color:var(--green);font:9px 'DM Mono',monospace}.history-status.returned{color:var(--blue)}.history-actions{display:flex;gap:6px}.history-actions button{padding:8px;border:1px solid var(--line);color:#b4bfbc;background:transparent;font:8px 'DM Mono',monospace}.history-actions button:hover{color:var(--orange-bright);border-color:var(--orange)}.pagination{margin-top:25px;text-align:center}.pagination a,.pagination span{display:inline-block;padding:8px 11px;color:var(--muted);font:9px 'DM Mono',monospace}.pagination .active{color:#170d08;background:var(--orange-bright)}.extend-dialog{width:min(420px,calc(100% - 36px));padding:0;border:1px solid #33444a;color:var(--text);background:#11191c;box-shadow:0 20px 60px rgba(0,0,0,.55)}.extend-dialog::backdrop{background:rgba(3,6,7,.75)}.extend-dialog-content{padding:24px}.extend-dialog h2{margin:8px 0 10px;color:#eaf0eb;font:600 25px/.95 Rajdhani,sans-serif}.extend-dialog p{color:var(--muted);font-size:10px;line-height:1.6}.extend-dialog-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:22px}.extend-dialog-actions button{padding:10px 13px;border:1px solid var(--line);color:#b4bfbc;background:transparent;font:8px 'DM Mono',monospace}.extend-dialog-actions button[type=submit]{color:#170d08;background:var(--orange-bright);border-color:var(--orange-bright)}@media(max-width:700px){.history-shell{padding:22px 18px}.history-row{grid-template-columns:48px 1fr;gap:12px}.history-dates,.history-status,.history-actions{grid-column:2}.history-title h1{font-size:39px}}
        .flash.error{color:#ff9470;border-color:#713f32;background:#271612}
    </style>
</head>
<body>
    @php
        $coverMeta = [
            'focus' => ['code' => 'NF / 2026', 'label' => 'THE', 'title' => 'ART OF<br>FOCUS'],
            'signal' => ['code' => 'DATA / 12', 'label' => 'THE', 'title' => 'SIGNAL<br>&amp; NOISE'],
            'seeing' => ['code' => 'ART / 04', 'label' => 'WAYS OF', 'title' => 'SEEING'],
            'tomorrow' => ['code' => 'FIC / 09', 'label' => 'TOMORROW, AND', 'title' => 'TOMORROW,<br>AND TOMORROW'],
        ];
    @endphp
    <main class="history-shell">
        <nav class="history-nav"><a href="{{ route('dashboard') }}">← COMMAND CENTER</a><a href="{{ route('collection.index') }}">EXPLORE COLLECTION ↗</a>@if (auth()->user()?->role === 'pustakawan')<a href="{{ route('librarian.index') }}">LIBRARY OPERATIONS ↗</a>@endif</nav>
        <header class="history-title"><p class="section-index">02 / BORROWING LOG</p><h1>MY<br><em>BORROWINGS</em></h1><p>Track every active and completed library mission.</p></header>
        @if(session('success'))<div class="flash">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="flash error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <div class="history-summary"><div><span>MISSIONS LOGGED</span><strong>{{ $borrowings->total() }}</strong></div><div><span>PAGE</span><strong>{{ $borrowings->currentPage() }}/{{ $borrowings->lastPage() }}</strong></div></div>
        <section class="history-list">
            @forelse($borrowings as $borrowing)
                @php($cover = $coverMeta[$borrowing->book->cover_theme] ?? $coverMeta['focus'])
                @php($daysUntilDue = now()->startOfDay()->diffInDays($borrowing->due_date, false))
                <article class="history-row">
                    <div class="book-cover history-cover cover-{{ $borrowing->book->cover_theme }}"><span class="cover-code">{{ $cover['code'] }}</span></div>
                    <div><span class="history-label">BORROWING CARD // #{{ str_pad($borrowing->id, 5, '0', STR_PAD_LEFT) }}</span><h2>{{ $borrowing->book->title }}</h2><p>{{ $borrowing->book->author }} // {{ $borrowing->book->category->name }}</p></div>
                    <div class="history-dates">ID #{{ str_pad($borrowing->id, 5, '0', STR_PAD_LEFT) }}<br>BORROWED {{ $borrowing->borrow_date->format('d M Y') }}<br>DUE {{ $borrowing->due_date->format('d M Y') }}@if($borrowing->is_active)<br>{{ $daysUntilDue >= 0 ? $daysUntilDue.' DAYS LEFT' : abs($daysUntilDue).' DAYS OVERDUE' }}@elseif($borrowing->return_date)<br>RETURNED {{ $borrowing->return_date->format('d M Y') }}@endif</div>
                    <div class="history-status {{ $borrowing->is_active ? '' : 'returned' }}">{{ strtoupper($borrowing->status) }}</div>
                    <div class="history-actions">
                        @if($borrowing->is_active)
                            <form method="POST" action="{{ route('borrowings.return', $borrowing) }}">@csrf<button type="submit">MARK RETURNED</button></form>
                                @if(!$borrowing->extended)<button type="button" data-extend-trigger data-extend-action="{{ route('borrowings.extend', $borrowing) }}" data-book-title="{{ $borrowing->book->title }}" data-current-due="{{ $borrowing->due_date->format('d M Y') }}">EXTEND +7</button>@endif
                        @endif
                    </div>
                </article>
            @empty
                <x-empty-state
                    title="NO BORROWING HISTORY FOUND."
                    message="Borrow a title from the collection to start your mission log."
                    action-label="EXPLORE COLLECTION"
                    :action-url="route('collection.index')"
                />
            @endforelse
        </section>
        @if($borrowings->hasPages())<div class="pagination">{{ $borrowings->links() }}</div>@endif
    </main>
    <dialog class="extend-dialog" id="extend-dialog" aria-labelledby="extend-dialog-title">
        <div class="extend-dialog-content">
            <p class="section-index">BORROWING CONTROL // EXTEND</p>
            <h2 id="extend-dialog-title">REQUEST EXTENSION?</h2>
            <p><strong id="extend-dialog-book"></strong><br>Current due date: <span id="extend-dialog-due"></span><br>The due date will move forward by 7 days.</p>
            <div class="extend-dialog-actions">
                <button type="button" id="extend-dialog-cancel">CANCEL</button>
                <form method="POST" id="extend-dialog-form">@csrf<button type="submit">CONFIRM +7 DAYS</button></form>
            </div>
        </div>
    </dialog>
    <script>
        (() => {
            const dialog = document.querySelector('#extend-dialog');
            const form = document.querySelector('#extend-dialog-form');
            const book = document.querySelector('#extend-dialog-book');
            const due = document.querySelector('#extend-dialog-due');
            document.querySelectorAll('[data-extend-trigger]').forEach((trigger) => {
                trigger.addEventListener('click', () => {
                    form.action = trigger.dataset.extendAction;
                    book.textContent = trigger.dataset.bookTitle;
                    due.textContent = trigger.dataset.currentDue;
                    dialog.showModal();
                });
            });
            document.querySelector('#extend-dialog-cancel')?.addEventListener('click', () => dialog.close());
        })();
    </script>
</body>
</html>
