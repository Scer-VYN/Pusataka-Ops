<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STACK // Register</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;500;600;700;800&family=Rajdhani:wght@500;600;700&display=swap');
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; display: grid; place-items: center; color: #edf2ef; background: #080b0d; font-family: 'Manrope', sans-serif; }
        .auth-shell { width: min(420px, calc(100% - 32px)); padding: 34px; border: 1px solid #2a373c; background: #101619; box-shadow: 0 30px 80px #0008; }
        .mark { color: #ff8240; font: 700 18px 'Rajdhani', sans-serif; letter-spacing: .14em; }
        .mark span { color: #68777d; font: 500 10px 'DM Mono', monospace; }
        .kicker { margin: 42px 0 12px; color: #e56c32; font: 10px 'DM Mono', monospace; letter-spacing: .15em; }
        h1 { margin: 0; font: 600 38px 'Rajdhani', sans-serif; letter-spacing: .07em; }
        .intro { color: #829097; font-size: 12px; line-height: 1.6; }
        label { display: block; margin: 17px 0 7px; color: #8c9a9d; font: 10px 'DM Mono', monospace; letter-spacing: .12em; }
        input { width: 100%; padding: 12px; border: 1px solid #33434a; outline: none; color: #edf2ef; background: #0b1012; }
        input[aria-invalid="true"] { border-color: #a24f38; }
        input:focus { border-color: #e56c32; }
        button { width: 100%; margin-top: 23px; padding: 13px; border: 0; color: #170d08; background: #ff8240; font-weight: 800; cursor: pointer; }
        button:disabled { cursor: wait; opacity: .65; }
        .error { margin-top: 7px; color: #ff9470; font-size: 11px; }
        .error[hidden] { display: none; }
        .hint { margin-top: 7px; color: #64747a; font: 10px 'DM Mono', monospace; }
        .form-status { min-height: 16px; margin-top: 18px; color: #ff9470; font: 10px 'DM Mono', monospace; letter-spacing: .04em; }
        .form-status.success { color: #9ccc5b; }
        .switch { margin: 24px 0 0; color: #718086; font-size: 11px; text-align: center; }
        a { color: #ff8240; text-decoration: none; }
    </style>
</head>
<body>
    <main class="auth-shell">
        <div class="mark">STACK<span>//01 · LIBRARY SYSTEM</span></div>
        <p class="kicker">NEW OPERATIVE // MEMBER REGISTRATION</p>
        <h1>JOIN STACK</h1>
        <p class="intro">Buat akun anggota untuk mulai menjelajahi koleksi perpustakaan.</p>
        <form id="register-form" method="POST" action="{{ route('register.store') }}" novalidate>
            @csrf
            <label for="name">FULL NAME</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" required autofocus>
            <div class="error" id="name-error" role="alert" @if(!$errors->has('name')) hidden @endif>@error('name'){{ $message }}@enderror</div>
            <label for="email">EMAIL</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" required>
            <div class="error" id="email-error" role="alert" @if(!$errors->has('email')) hidden @endif>@error('email'){{ $message }}@enderror</div>
            <label for="password">PASSWORD</label>
            <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" required>
            <p class="hint">MINIMUM 8 CHARACTERS</p>
            <div class="error" id="password-error" role="alert" @if(!$errors->has('password')) hidden @endif>@error('password'){{ $message }}@enderror</div>
            <label for="password_confirmation">CONFIRM PASSWORD</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password-confirmation-error" required>
            <div class="error" id="password-confirmation-error" role="alert" @if(!$errors->has('password_confirmation')) hidden @endif>@error('password_confirmation'){{ $message }}@enderror</div>
            <div id="register-status" class="form-status" role="status" aria-live="polite"></div>
            <button id="register-submit" type="submit">CREATE MEMBER ACCOUNT ↗</button>
        </form>
        <p class="switch">Sudah punya akun? <a href="{{ route('login') }}">Kembali ke sign in</a></p>
    </main>
    <script>
        (() => {
            const form = document.querySelector('#register-form');
            if (!form) return;

            const fields = {
                name: form.querySelector('#name'),
                email: form.querySelector('#email'),
                password: form.querySelector('#password'),
                passwordConfirmation: form.querySelector('#password_confirmation'),
            };
            const status = form.querySelector('#register-status');
            const submitButton = form.querySelector('#register-submit');

            const getErrorElement = (field) => document.querySelector(`#${field.id}-error`);

            const validate = (field) => {
                const value = field.value.trim();

                if (!value) {
                    return field === fields.passwordConfirmation
                        ? 'Konfirmasi kata sandi wajib diisi.'
                        : `${field.labels[0].textContent} wajib diisi.`;
                }

                if (field === fields.name && value.length > 255) {
                    return 'Nama lengkap maksimal 255 karakter.';
                }

                if (field === fields.email && field.validity.typeMismatch) {
                    return 'Masukkan alamat email yang valid.';
                }

                if (field === fields.password && value.length < 8) {
                    return 'Kata sandi minimal 8 karakter.';
                }

                if (field === fields.passwordConfirmation && value !== fields.password.value) {
                    return 'Konfirmasi kata sandi tidak cocok.';
                }

                return '';
            };

            const setError = (field, message) => {
                const errorElement = getErrorElement(field);
                errorElement.textContent = message;
                errorElement.hidden = !message;
                field.setAttribute('aria-invalid', message ? 'true' : 'false');
            };

            const validateField = (field) => {
                const message = validate(field);
                setError(field, message);
                return !message;
            };

            Object.values(fields).forEach((field) => {
                field.addEventListener('blur', () => validateField(field));
                field.addEventListener('input', () => {
                    if (field.getAttribute('aria-invalid') === 'true') {
                        validateField(field);
                    }

                    if (field === fields.password && fields.passwordConfirmation.value) {
                        validateField(fields.passwordConfirmation);
                    }
                });
            });

            form.addEventListener('submit', (event) => {
                const isValid = Object.values(fields).map(validateField).every(Boolean);

                if (!isValid) {
                    event.preventDefault();
                    status.textContent = 'FORM INVALID // PERIKSA DATA YANG DITANDAI.';
                    status.className = 'form-status';
                    const firstInvalidField = Object.values(fields).find((field) => field.getAttribute('aria-invalid') === 'true');
                    firstInvalidField?.focus();
                    return;
                }

                status.textContent = 'VALIDATION PASSED // CREATING MEMBER ACCOUNT...';
                status.className = 'form-status success';
                submitButton.disabled = true;
            });
        })();
    </script>
</body>
</html>
