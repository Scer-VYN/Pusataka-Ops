<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $book->title }} // STACK</title>
    @include('partials.preferences')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/theme.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
    @endif
    <style>
        .message.error{border-color:#713f32;color:#ff9470;background:#271612}
    </style>
</head>
<body class="detail-page">
    @php
        $coverMeta = [
            'focus' => ['code' => 'NF / 2026', 'label' => 'THE', 'title' => 'ART OF<br>FOCUS'],
            'signal' => ['code' => 'DATA / 12', 'label' => 'THE', 'title' => 'SIGNAL<br>&amp; NOISE'],
            'seeing' => ['code' => 'ART / 04', 'label' => 'WAYS OF', 'title' => 'SEEING'],
            'tomorrow' => ['code' => 'FIC / 09', 'label' => 'TOMORROW, AND', 'title' => 'TOMORROW,<br>AND TOMORROW'],
        ];
        $cover = $coverMeta[$book->cover_theme] ?? $coverMeta['focus'];
    @endphp
    <main class="detail-shell" aria-labelledby="book-title">
        <nav class="detail-nav"><a href="{{ route('collection.index') }}">← BACK TO COLLECTION</a><a href="{{ route('borrowings.index') }}">MY BORROWINGS ↗</a>@if (auth()->user()?->role === 'pustakawan')<a href="{{ route('librarian.index') }}">LIBRARY OPERATIONS ↗</a>@endif</nav>
        <section class="detail-grid">
            <div class="book-cover detail-cover cover-{{ $book->cover_theme }}"><span class="cover-code">{{ $cover['code'] }}</span><div><small>{{ $cover['label'] }}</small><strong>{!! $cover['title'] !!}</strong><i></i><em>{{ strtoupper($book->author) }}</em></div></div>
            <div class="detail-copy">
                <p class="section-index">{{ strtoupper($book->category->name) }} // RACK {{ $book->category->rack }}</p>
                <h1 id="book-title">{{ $book->title }}</h1>
                <p class="author">{{ $book->author }} · {{ $book->publisher }}</p>
                <p class="detail-status {{ $book->available_stock < 1 ? 'unavailable' : '' }}" role="status">{{ $book->available_stock ? 'AVAILABLE FOR BORROWING' : 'CURRENTLY ON LOAN' }} · {{ $book->available_stock }}/{{ $book->total_stock }} COPIES READY</p>
                <p class="description">{{ $book->description }}</p>
                <div class="meta-grid"><div><span>TOTAL STOCK</span><strong>{{ $book->total_stock }}</strong></div><div><span>AVAILABLE</span><strong>{{ $book->available_stock }}</strong></div><div><span>STATUS</span><strong>{{ $book->available_stock ? 'READY' : 'ON LOAN' }}</strong></div></div>
                @if(session('success'))<div class="message" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="message error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                <form id="detail-borrow-form" class="borrow-form" method="POST" action="{{ route('borrowings.store') }}">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $book->id }}">
                    <label for="detail-borrow-date">START DATE<input id="detail-borrow-date" type="date" name="borrow_date" value="{{ old('borrow_date', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" aria-describedby="detail-borrow-error" required></label>
                    <label for="detail-borrow-duration">DURATION<select id="detail-borrow-duration" name="duration" aria-describedby="detail-borrow-error"><option value="7" @selected(old('duration') == '7')>7 DAYS</option><option value="14" @selected(old('duration', '14') == '14')>14 DAYS</option><option value="21" @selected(old('duration') == '21')>21 DAYS</option><option value="30" @selected(old('duration') == '30')>30 DAYS</select></label>
                    <div id="detail-borrow-error" class="form-error" role="alert" hidden></div>
                    <button type="submit" @disabled($book->available_stock < 1)>{{ $book->available_stock ? 'REQUEST BORROW ↗' : 'CURRENTLY UNAVAILABLE' }}</button>
                </form>
            </div>
        </section>
    </main>
    <script>
        (() => {
            const form = document.querySelector('#detail-borrow-form');
            if (!form) return;

            const dateInput = form.querySelector('#detail-borrow-date');
            const durationInput = form.querySelector('#detail-borrow-duration');
            const errorElement = form.querySelector('#detail-borrow-error');
            const submitButton = form.querySelector('button[type="submit"]');
            const allowedDurations = ['7', '14', '21', '30'];

            const validate = () => {
                const errors = [];
                const today = dateInput.min || new Date().toISOString().slice(0, 10);

                if (!dateInput.value) {
                    errors.push('Select a start date.');
                    dateInput.setAttribute('aria-invalid', 'true');
                } else if (dateInput.value < today) {
                    errors.push('Start date cannot be in the past.');
                    dateInput.setAttribute('aria-invalid', 'true');
                } else {
                    dateInput.setAttribute('aria-invalid', 'false');
                }

                if (!allowedDurations.includes(durationInput.value)) {
                    errors.push('Select a valid borrowing duration.');
                    durationInput.setAttribute('aria-invalid', 'true');
                } else {
                    durationInput.setAttribute('aria-invalid', 'false');
                }

                errorElement.textContent = errors.join(' ');
                errorElement.hidden = errors.length === 0;
                return errors.length === 0;
            };

            [dateInput, durationInput].forEach((field) => {
                field.addEventListener('blur', validate);
                field.addEventListener('change', validate);
            });

            form.addEventListener('submit', (event) => {
                if (!validate() || !window.confirm('Confirm this borrowing request?')) {
                    event.preventDefault();
                    if (errorElement.hidden) {
                        return;
                    }
                    dateInput.focus();
                    return;
                }

                submitButton.disabled = true;
            });
        })();
    </script>
</body>
</html>
