<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>STACK // Password Recovery</title>
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
        label { display: block; margin: 26px 0 7px; color: #8c9a9d; font: 10px 'DM Mono', monospace; letter-spacing: .12em; }
        input { width: 100%; padding: 12px; border: 1px solid #33434a; outline: none; color: #edf2ef; background: #0b1012; }
        input[aria-invalid="true"] { border-color: #a24f38; }
        input:focus { border-color: #e56c32; }
        button { width: 100%; margin-top: 23px; padding: 13px; border: 0; color: #170d08; background: #ff8240; font-weight: 800; cursor: pointer; }
        button:hover { background: #ff9b60; }
        button:disabled { cursor: wait; opacity: .65; }
        .form-status { min-height: 16px; margin-top: 18px; color: #ff9470; font: 10px 'DM Mono', monospace; line-height: 1.5; }
        .form-status.success { color: #9ccc5b; }
        .switch { margin: 24px 0 0; color: #718086; font-size: 11px; text-align: center; }
        a { color: #ff8240; text-decoration: none; }
    </style>
    <script>{!! file_get_contents(resource_path('js/theme.js')) !!}</script>
</head>
<body>
    <main class="auth-shell">
        <div class="mark">STACK<span>//01 · LIBRARY SYSTEM</span></div>
        <p class="kicker">RECOVERY CHANNEL // IDENTITY CHECK</p>
        <h1>RESET ACCESS</h1>
        <p class="intro">Masukkan email terdaftar untuk menerima tautan pemulihan kata sandi.</p>
        <form id="forgot-password-form" novalidate>
            <label for="email">REGISTERED EMAIL</label>
            <input id="email" name="email" type="email" maxlength="255" autocomplete="email" aria-invalid="false" aria-describedby="email-error" required autofocus>
            <div class="form-status" id="email-error" role="alert"></div>
            <div class="form-status" id="form-status" role="status" aria-live="polite"></div>
            <button id="forgot-password-submit" type="submit">REQUEST RESET LINK ↗</button>
        </form>
        <p class="switch">Ingat kata sandi? <a href="{{ route('login') }}">Kembali ke sign in</a></p>
    </main>
    <script>
        (() => {
            const form = document.querySelector('#forgot-password-form');
            const email = document.querySelector('#email');
            const emailError = document.querySelector('#email-error');
            const status = document.querySelector('#form-status');
            const submit = document.querySelector('#forgot-password-submit');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const valid = email.validity.valid && email.value.trim() !== '';
                email.setAttribute('aria-invalid', valid ? 'false' : 'true');

                if (!valid) {
                    emailError.textContent = 'MASUKKAN EMAIL YANG VALID.';
                    email.focus();
                    return;
                }

                emailError.textContent = '';
                status.className = 'form-status';
                status.textContent = 'REQUESTING RESET LINK...';
                submit.disabled = true;

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
                    status.className = 'form-status success';
                    status.textContent = result.message.toUpperCase();
                } catch (error) {
                    status.className = 'form-status';
                    status.textContent = error.message;
                } finally {
                    submit.disabled = false;
                }
            });
        })();
    </script>
</body>
</html>
