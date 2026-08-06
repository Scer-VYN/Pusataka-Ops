<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STACK // Sign in</title>
    @include('partials.preferences')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&family=Rajdhani:wght@500;600;700&display=swap');
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; color: #edf2ef; background: #080b0d; font-family: 'Manrope', sans-serif; }
        .auth-shell { width: min(420px, calc(100% - 32px)); padding: 34px; border: 1px solid #2a373c; background: #101619; box-shadow: 0 30px 80px #0008; }
        .mark { color: #ff8240; font: 700 18px 'Rajdhani', sans-serif; letter-spacing: .14em; }
        .mark span { color: #68777d; font: 500 10px 'DM Mono', monospace; }
        .kicker { margin: 55px 0 12px; color: #e56c32; font: 10px 'DM Mono', monospace; letter-spacing: .15em; }
        h1 { margin: 0; font: 600 38px 'Rajdhani', sans-serif; letter-spacing: .07em; }
        .intro { color: #829097; font-size: 12px; line-height: 1.6; }
        label { display: block; margin: 22px 0 7px; color: #8c9a9d; font: 10px 'DM Mono', monospace; letter-spacing: .12em; }
        input { width: 100%; padding: 12px; border: 1px solid #33434a; outline: none; color: #edf2ef; background: #0b1012; }
        input[aria-invalid="true"] { border-color: #a24f38; }
        input:focus { border-color: #e56c32; }
        button { width: 100%; margin-top: 23px; padding: 13px; border: 0; color: #170d08; background: #ff8240; font-weight: 800; cursor: pointer; }
        button:hover { background: #ff9b60; }
        .error { margin-top: 7px; color: #ff9470; font-size: 11px; }
        .session-expired { margin-top: 18px; padding: 10px; border: 1px solid rgba(229,108,50,.35); color: #ffb18d; background: rgba(229,108,50,.08); font: 9px 'DM Mono', monospace; line-height: 1.5; }
        .system-status { display: flex; align-items: center; gap: 7px; margin-top: 20px; color: #64747a; font: 9px 'DM Mono', monospace; letter-spacing: .06em; }
        .system-status i { width: 6px; height: 6px; border-radius: 50%; background: #9ccc5b; box-shadow: 0 0 0 4px rgba(156,204,91,.09); }
        .switch { margin: 24px 0 0; color: #718086; font-size: 11px; text-align: center; }
        a { color: #ff8240; text-decoration: none; }
    </style>
    <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
</head>
<body>
    <main class="auth-shell">
        <div class="mark">STACK<span>//01 · LIBRARY SYSTEM</span></div>
        <p class="kicker">SECURE ACCESS // MEMBER PORTAL</p>
        <h1>SIGN IN</h1>
        <p class="intro">Masuk untuk melihat koleksi, peminjaman, dan pengingat pengembalian.</p>
        @if (request()->query('session') === 'expired')
            <div class="session-expired" role="alert">Your session expired. Sign in again to continue.</div>
        @endif
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">EMAIL</label>
            <input id="email" name="email" type="email" maxlength="255" value="{{ old('email') }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" required autofocus>
            @error('email')<div class="error" id="email-error" role="alert">{{ $message }}</div>@enderror
            @if (session('login_error'))<div class="error" role="alert">{{ session('login_error') }}</div>@endif
            <label for="password">PASSWORD</label>
            <input id="password" name="password" type="password" autocomplete="current-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" required>
            @error('password')<div class="error" id="password-error" role="alert">{{ $message }}</div>@enderror
            <p class="switch" style="margin-top:14px; text-align:right;"><a href="{{ route('password.request') }}">LUPA PASSWORD?</a></p>
            <button type="submit">ENTER COMMAND CENTER ↗</button>
        </form>
        <div class="system-status"><i></i><span>SECURE CHANNEL // READY</span></div>
        <p class="switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sebagai anggota</a></p>
    </main>
</body>
</html>
