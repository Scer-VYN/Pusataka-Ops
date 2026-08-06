<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080b0d">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>STACK // Account</title>
    @include('partials.preferences')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/theme.js'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
        <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
    @endif
    <style>
        .account-shell { max-width: 1120px; margin: 0 auto; padding: 30px 42px 70px; }
        .account-nav { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding-bottom:24px; border-bottom:1px solid var(--line-soft); }
        .account-brand { color:var(--orange-bright); font:700 18px Rajdhani,sans-serif; letter-spacing:.12em; }
        .account-links { display:flex; align-items:center; gap:18px; color:#829097; font:9px 'DM Mono',monospace; text-transform:uppercase; }
        .account-links a:hover { color:var(--orange-bright); }
        .account-links button { color:#829097; background:transparent; font:9px 'DM Mono',monospace; text-transform:uppercase; }
        .account-links button:hover { color:var(--orange-bright); }
        .account-shell .account-panel { margin-top:52px; padding-top:0; border-top:0; }
        .account-shell .account-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:20px; }
        .account-shell .account-heading h1 { font-size:48px; }
        .account-shell .account-heading p:last-child { margin-top:10px; color:var(--muted); font-size:11px; }
        .account-status { color:var(--green); font:8px 'DM Mono',monospace; letter-spacing:.1em; }
        .account-shell .account-grid { margin-top:28px; }
        .account-shell > .breadcrumb { margin-top:24px; }
        .profile-heading { display:flex; align-items:center; gap:12px; }
        .profile-heading .account-label { margin:0; }
        .profile-heading h3 { margin-top:5px; }
        .avatar-upload { display:flex; align-items:center; gap:12px; margin-top:18px; }
        .avatar-preview { width:52px; height:52px; font-size:16px; transition:background-image .2s; }
        .avatar-preview.has-image { color:transparent; background-position:center; background-size:cover; }
        .avatar-upload label { display:grid; gap:6px; color:#68787d; font:7px 'DM Mono',monospace; letter-spacing:.1em; }
        .avatar-upload input { width:100%; color:var(--muted); font-size:9px; }
        .avatar-status { margin-top:5px; color:#68787d; font:7px 'DM Mono',monospace; }
        .password-recovery { margin-top:18px; border-top:1px solid var(--line-soft); }
        .password-recovery summary { padding-top:14px; color:var(--orange-bright); cursor:pointer; font:8px 'DM Mono',monospace; letter-spacing:.08em; }
        .password-recovery summary::marker { color:var(--orange-bright); }
        .recovery-form { display:grid; gap:9px; margin-top:15px; }
        .recovery-form label { display:grid; gap:6px; color:#68787d; font:7px 'DM Mono',monospace; letter-spacing:.1em; }
        .recovery-form input { width:100%; padding:9px; border:1px solid var(--line); color:var(--text); background:#0b1012; font-size:10px; outline:0; }
        .recovery-form input:focus { border-color:var(--orange); }
        .recovery-form input[aria-invalid="true"] { border-color:#a24f38; }
        .recovery-form button { margin-top:4px; }
        .recovery-status { min-height:14px; color:#ff9470; font:8px 'DM Mono',monospace; line-height:1.5; }
        .recovery-status.success { color:var(--green); }
        .preference-status { min-height:14px; margin-top:18px; color:#ff9470; font:8px 'DM Mono',monospace; line-height:1.5; }
        .preference-status.success { color:var(--green); }
        .preference-list { display:grid; gap:9px; margin-top:22px; }
        .preference-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 0; border-top:1px solid var(--line-soft); color:#aab8b5; font:8px 'DM Mono',monospace; }
        .preference-row span:first-child { display:grid; gap:4px; }
        .preference-row small { color:#68787d; font-size:7px; }
        .preference-switch { position:absolute; width:1px; height:1px; opacity:0; }
        .switch-track { position:relative; flex:0 0 auto; width:34px; height:18px; border:1px solid #405057; background:#172126; transition:background .2s,border-color .2s; }
        .switch-track::after { position:absolute; top:3px; left:3px; width:10px; height:10px; background:#718287; content:''; transition:transform .2s,background .2s; }
        .preference-switch:checked + .switch-track { border-color:var(--orange); background:rgba(229,108,50,.2); }
        .preference-switch:checked + .switch-track::after { background:var(--orange-bright); transform:translateX(16px); }
        .preference-switch:focus-visible + .switch-track { outline:2px solid var(--orange-bright); outline-offset:3px; }
        @media(max-width:650px) {
            .account-shell { padding:22px 18px 50px; }
            .account-nav { align-items:flex-start; gap:16px; }
            .account-links { flex-wrap:wrap; justify-content:flex-end; gap:8px; }
            .account-shell .account-heading { display:block; }
            .account-shell .account-heading h1 { font-size:39px; }
            .account-status { display:block; margin-top:18px; }
        }
    </style>
</head>
@php
    $avatarUrl = $user->avatar ? \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) : null;
    $avatarInitial = strtoupper(substr($user->name, 0, 1));
@endphp
<body class="collection-page">
    <main class="account-shell">
        <nav class="account-nav" aria-label="Account navigation">
            <a class="account-brand" href="{{ route('dashboard') }}">STACK<span style="color:#68777d">//01</span></a>
            <div class="account-links">
                <a href="{{ route('dashboard') }}">Command center</a>
                <a href="{{ route('collection.index') }}">Explore collection</a>
                <a href="{{ route('borrowings.index') }}">My borrowings</a>
                @if ($user->role === 'pustakawan')
                    <a href="{{ route('librarian.index') }}">Library operations</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Sign out</button></form>
            </div>
        </nav>
        <x-breadcrumb current="ACCOUNT" home-label="COMMAND CENTER" :home-url="route('dashboard')" />

        <section class="account-panel" id="settings" aria-labelledby="account-title">
            <div class="account-heading">
                <div>
                    <p class="section-index">04 / ACCOUNT</p>
                    <h1 id="account-title">SETTINGS &amp;<br><em>PROFILE</em></h1>
                    <p>Manage the account currently connected to this library session.</p>
                </div>
                <span class="account-status">● ACCOUNT ACTIVE</span>
            </div>
            <div class="account-grid">
                <article class="account-card" id="profile">
                    <div class="profile-heading">
                        <div class="avatar avatar-orange{{ $avatarUrl ? ' has-image' : '' }}" aria-label="Profile avatar" @if($avatarUrl) style="background-image: url('{{ $avatarUrl }}'); background-position: center; background-size: cover; color: transparent;" @endif>{{ $avatarInitial }}</div>
                        <div>
                            <p class="account-label">PROFILE</p>
                            <h3>{{ $user->name }}</h3>
                        </div>
                    </div>
                    <dl class="account-details">
                        <div><dt>EMAIL</dt><dd>{{ $user->email }}</dd></div>
                        <div><dt>ROLE</dt><dd>{{ strtoupper($user->role) }}</dd></div>
                        <div><dt>MEMBER ID</dt><dd>{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</dd></div>
                        <div><dt>JOINED</dt><dd>{{ $user->created_at->format('d M Y') }}</dd></div>
                    </dl>
                    @if(session('profile_success'))<div class="account-message" role="status">{{ session('profile_success') }}</div>@endif
                    @if($errors->hasAny(['name', 'email', 'avatar']))<div class="account-message error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                    <form class="account-form" method="POST" action="{{ route('account.avatar.update') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="avatar-upload">
                            <div class="avatar avatar-orange avatar-preview{{ $avatarUrl ? ' has-image' : '' }}" id="profile-avatar-preview" aria-label="Profile avatar" @if($avatarUrl) style="background-image: url('{{ $avatarUrl }}');" @endif>{{ $avatarInitial }}</div>
                            <label for="profile-avatar">AVATAR<input id="profile-avatar" name="avatar" type="file" accept="image/png,image/jpeg,image/webp" aria-describedby="avatar-status"></label>
                        </div>
                        <div class="avatar-status" id="avatar-status" role="status">SELECT AN IMAGE TO PREVIEW.</div>
                        <button class="outline-button" type="submit"><span>UPLOAD AVATAR</span><span>↗</span></button>
                    </form>
                    <form class="account-form" method="POST" action="{{ route('account.profile.update') }}">
                        @csrf
                        @method('PATCH')
                        <label for="profile-name">NAME<input id="profile-name" name="name" maxlength="255" value="{{ old('name', $user->name) }}" autocomplete="name" required></label>
                        <label for="profile-email">EMAIL<input id="profile-email" name="email" type="email" maxlength="255" value="{{ old('email', $user->email) }}" autocomplete="email" required></label>
                        <button class="outline-button" type="submit"><span>UPDATE PROFILE</span><span>↗</span></button>
                    </form>
                </article>
                <article class="account-card" id="security">
                    <p class="account-label">SESSION SETTINGS</p>
                    <h3>SECURE ACCESS</h3>
                    <p class="account-copy">Change your password without leaving the library session.</p>
                    @if(session('password_success'))<div class="account-message" role="status">{{ session('password_success') }}</div>@endif
                    @if($errors->hasAny(['current_password', 'password', 'password_confirmation']))<div class="account-message error" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                    <form class="account-form" method="POST" action="{{ route('account.password.update') }}">
                        @csrf
                        @method('PUT')
                        <label for="current-password">CURRENT PASSWORD<input id="current-password" name="current_password" type="password" autocomplete="current-password" required></label>
                        <label for="new-password">NEW PASSWORD<input id="new-password" name="password" type="password" minlength="8" autocomplete="new-password" required></label>
                        <label for="password-confirmation">CONFIRM PASSWORD<input id="password-confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></label>
                        <button class="outline-button" type="submit"><span>UPDATE PASSWORD</span><span>↗</span></button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="outline-button" type="submit"><span>SIGN OUT</span><span>↪</span></button>
                    </form>
                    <details class="password-recovery" id="forgot-password">
                        <summary>FORGOT PASSWORD? OPEN RECOVERY CHANNEL</summary>
                        <form class="recovery-form" id="forgot-password-form" novalidate>
                            <label for="recovery-email">REGISTERED EMAIL<input id="recovery-email" name="email" type="email" maxlength="255" value="{{ $user->email }}" autocomplete="email" aria-describedby="recovery-status" required></label>
                            <div class="recovery-status" id="recovery-status" role="status" aria-live="polite"></div>
                            <button class="outline-button" type="submit"><span>REQUEST RESET LINK</span><span>↗</span></button>
                        </form>
                    </details>
                </article>
                <article class="account-card" id="preferences">
                    <p class="account-label">PREFERENCES</p>
                    <h3>FIELD CONFIG</h3>
                    <p class="account-copy">Tune the interface and briefing signals for this library session.</p>
                    <div class="preference-list">
                        <label class="preference-row" for="theme-toggle">
                            <span>LIGHT THEME<small>Switch from Black Ops dark mode.</small></span>
                            <input class="preference-switch" id="theme-toggle" type="checkbox" role="switch" aria-label="Enable light theme" @checked($user->theme === 'light')>
                            <span class="switch-track" aria-hidden="true"></span>
                        </label>
                        <label class="preference-row" for="notifications-toggle">
                            <span>LIBRARY NOTIFICATIONS<small>Show return reminders and activity updates.</small></span>
                            <input class="preference-switch" id="notifications-toggle" type="checkbox" role="switch" aria-label="Enable library notifications" @checked($user->notifications_enabled)>
                            <span class="switch-track" aria-hidden="true"></span>
                        </label>
                    </div>
                    <div class="preference-status" id="preference-status" role="status" aria-live="polite"></div>
                </article>
            </div>
        </section>
    </main>
    <script>
        (() => {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const form = document.querySelector('#forgot-password-form');
            const email = document.querySelector('#recovery-email');
            const status = document.querySelector('#recovery-status');
            const avatarInput = document.querySelector('#profile-avatar');
            const avatarPreview = document.querySelector('#profile-avatar-preview');
            const avatarStatus = document.querySelector('#avatar-status');

            avatarInput?.addEventListener('change', () => {
                const file = avatarInput.files?.[0];

                if (!file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    avatarInput.value = '';
                    avatarStatus.textContent = 'SELECT A PNG, JPEG, OR WEBP IMAGE.';
                    return;
                }

                const reader = new FileReader();
                reader.addEventListener('load', () => {
                    if (typeof reader.result !== 'string') {
                        return;
                    }

                    avatarPreview.style.backgroundImage = `url(${reader.result})`;
                    avatarPreview.classList.add('has-image');
                    avatarStatus.textContent = 'AVATAR PREVIEW READY.';
                });
                reader.readAsDataURL(file);
            });

            form?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const valid = email.validity.valid && email.value.trim() !== '';
                email.setAttribute('aria-invalid', valid ? 'false' : 'true');

                if (!valid) {
                    status.className = 'recovery-status';
                    status.textContent = 'ENTER A VALID REGISTERED EMAIL.';
                    email.focus();
                    return;
                }

                status.className = 'recovery-status';
                status.textContent = 'REQUESTING RESET LINK...';

                try {
                    const response = await fetch('{{ url('/api/auth/forgot-password') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({ email: email.value.trim() }),
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message ?? 'RESET REQUEST FAILED.');
                    }
                    status.className = 'recovery-status success';
                    status.textContent = result.message.toUpperCase();
                } catch (error) {
                    status.className = 'recovery-status';
                    status.textContent = error.message;
                }
            });

            const themeToggle = document.querySelector('#theme-toggle');
            const notificationsToggle = document.querySelector('#notifications-toggle');
            const preferenceStatus = document.querySelector('#preference-status');
            const setTheme = (theme) => window.libraryPreferences?.setTheme(theme);
            const setNotifications = (enabled) => window.libraryPreferences?.setNotificationsEnabled(enabled);
            let preferenceQueue = Promise.resolve();

            const queuePreferenceSave = (field, value, previousValue) => {
                const input = field === 'theme' ? themeToggle : notificationsToggle;
                const applyLocalValue = (nextValue) => {
                    if (field === 'theme') {
                        setTheme(nextValue);
                    } else {
                        setNotifications(nextValue);
                    }
                };

                applyLocalValue(value);
                preferenceQueue = preferenceQueue.then(async () => {
                    preferenceStatus.className = 'preference-status';
                    preferenceStatus.textContent = 'SAVING PREFERENCES...';

                    try {
                        const response = await fetch('{{ url('/api/preferences') }}', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ [field]: value }),
                        });
                        const result = await response.json();
                        if (!response.ok) {
                            throw new Error(result.message ?? 'PREFERENCE UPDATE FAILED.');
                        }
                        preferenceStatus.className = 'preference-status success';
                        preferenceStatus.textContent = 'PREFERENCES SAVED.';
                    } catch (error) {
                        const currentValue = field === 'theme'
                            ? (input.checked ? 'light' : 'dark')
                            : input.checked;
                        if (currentValue === value) {
                            input.checked = field === 'theme'
                                ? previousValue === 'light'
                                : previousValue;
                            applyLocalValue(previousValue);
                        }
                        preferenceStatus.className = 'preference-status';
                        preferenceStatus.textContent = error.message;
                    }
                });
            };

            themeToggle?.addEventListener('change', () => queuePreferenceSave(
                'theme',
                themeToggle.checked ? 'light' : 'dark',
                themeToggle.checked ? 'dark' : 'light',
            ));
            notificationsToggle?.addEventListener('change', () => queuePreferenceSave(
                'notifications_enabled',
                notificationsToggle.checked,
                !notificationsToggle.checked,
            ));
        })();
    </script>
</body>
</html>
