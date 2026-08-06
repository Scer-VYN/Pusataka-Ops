<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>STACK // New Password</title>
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
        label { display: block; margin: 21px 0 7px; color: #8c9a9d; font: 10px 'DM Mono', monospace; letter-spacing: .12em; }
        input { width: 100%; padding: 12px; border: 1px solid #33434a; outline: none; color: #edf2ef; background: #0b1012; }
        input[aria-invalid="true"] { border-color: #a24f38; }
        input:focus { border-color: #e56c32; }
        button { width: 100%; margin-top: 23px; padding: 13px; border: 0; color: #170d08; background: #ff8240; font-weight: 800; cursor: pointer; }
        button:hover { background: #ff9b60; }
        button:disabled { cursor: wait; opacity: .65; }
        .error { min-height: 14px; margin-top: 7px; color: #ff9470; font: 10px 'DM Mono', monospace; line-height: 1.5; }
        .status { min-height: 16px; margin-top: 18px; color: #ff9470; font: 10px 'DM Mono', monospace; line-height: 1.5; }
        .status.success { color: #9ccc5b; }
        .switch { margin: 24px 0 0; color: #718086; font-size: 11px; text-align: center; }
        a { color: #ff8240; text-decoration: none; }
    </style>
    <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
</head>
<body>
    <main class="auth-shell">
        <div class="mark">STACK<span>//01 · LIBRARY SYSTEM</span></div>
        <p class="kicker">RECOVERY CHANNEL // NEW CREDENTIAL</p>
        <h1>SET PASSWORD</h1>
        <p class="intro">Buat kata sandi baru untuk mengamankan kembali akses library session.</p>
        <form id="reset-password-form" data-token="{{ $token }}" novalidate>
            <label for="email">REGISTERED EMAIL</label>
            <input id="email" name="email" type="email" maxlength="255" value="{{ $email }}" autocomplete="email" aria-invalid="false" required>
            <label for="password">NEW PASSWORD</label>
            <input id="password" name="password" type="password" minlength="8" autocomplete="new-password" aria-invalid="false" required>
            <label for="password-confirmation">CONFIRM PASSWORD</label>
            <input id="password-confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" aria-invalid="false" required>
            <div class="error" id="form-error" role="alert"></div>
            <div class="status" id="form-status" role="status" aria-live="polite"></div>
            <button id="reset-password-submit" type="submit">SAVE NEW PASSWORD ↗</button>
        </form>
        <p class="switch">Kembali ke <a href="{{ route('login') }}">sign in</a></p>
    </main>
    <script>
        (() => {
            const form = document.querySelector('#reset-password-form');
            const email = document.querySelector('#email');
            const password = document.querySelector('#password');
            const confirmation = document.querySelector('#password-confirmation');
            const error = document.querySelector('#form-error');
            const status = document.querySelector('#form-status');
            const submit = document.querySelector('#reset-password-submit');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                error.textContent = '';
                const validEmail = email.validity.valid && email.value.trim() !== '';
                const validPassword = password.value.length >= 8;
                const matchingPasswords = password.value === confirmation.value;

                if (!validEmail || !validPassword || !matchingPasswords) {
                    email.setAttribute('aria-invalid', validEmail ? 'false' : 'true');
                    password.setAttribute('aria-invalid', validPassword ? 'false' : 'true');
                    confirmation.setAttribute('aria-invalid', matchingPasswords ? 'false' : 'true');
                    error.textContent = !validEmail
                        ? 'MASUKKAN EMAIL YANG VALID.'
                        : !validPassword
                            ? 'PASSWORD MINIMAL 8 KARAKTER.'
                            : 'KONFIRMASI PASSWORD TIDAK COCOK.';
                    return;
                }

                status.className = 'status';
                status.textContent = 'UPDATING CREDENTIAL...';
                submit.disabled = true;

                try {
                    const response = await fetch('{{ url('/api/auth/reset-password') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({
                            token: form.dataset.token,
                            email: email.value.trim(),
                            password: password.value,
                            password_confirmation: confirmation.value,
                        }),
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message ?? 'PASSWORD RESET FAILED.');
                    }
                    status.className = 'status success';
                    status.textContent = 'PASSWORD UPDATED. REDIRECTING TO SIGN IN...';
                    window.setTimeout(() => { window.location.href = '{{ route('login') }}'; }, 1200);
                } catch (resetError) {
                    status.className = 'status';
                    status.textContent = resetError.message;
                    submit.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>
