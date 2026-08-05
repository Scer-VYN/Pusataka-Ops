<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#080b0d">
    <title>STACK // Data Not Found</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css'])
    @else
        <style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
    @endif
    <style>
        .error-shell{display:grid;align-content:space-between;min-height:100vh;max-width:980px;margin:0 auto;padding:30px 42px 55px}.error-nav{display:flex;align-items:center;justify-content:space-between;padding-bottom:24px;border-bottom:1px solid var(--line-soft)}.error-nav strong{color:var(--orange-bright);font:700 18px Rajdhani,sans-serif;letter-spacing:.12em}.error-nav span,.error-nav a{color:var(--muted);font:9px 'DM Mono',monospace}.error-nav a:hover{color:var(--orange-bright)}.error-content{max-width:620px;padding:90px 0}.error-code{color:var(--orange);font:10px 'DM Mono',monospace;letter-spacing:.16em}.error-content h1{margin:16px 0 20px;color:var(--text);font:600 clamp(48px,9vw,92px)/.82 Rajdhani,sans-serif;letter-spacing:.03em}.error-content h1 em{color:var(--orange-bright);font-style:normal}.error-content p{max-width:480px;color:var(--muted);font-size:12px;line-height:1.8}.error-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:28px}.error-actions a{padding:12px 15px;color:#170d08;background:var(--orange-bright);font:800 9px 'DM Mono',monospace}.error-actions a.secondary{color:var(--text);border:1px solid var(--line);background:transparent}.error-actions a:hover{filter:brightness(1.12)}.error-footer{color:#506066;font:8px 'DM Mono',monospace;letter-spacing:.08em}@media(max-width:600px){.error-shell{padding:22px 18px 35px}.error-content{padding:70px 0}.error-content h1{font-size:57px}}
    </style>
</head>
<body>
    <main class="error-shell">
        <nav class="error-nav">
            <strong>STACK<span style="color:#68777d">//01</span></strong>
            <span>LIBRARY SYSTEM // RECOVERY CHANNEL</span>
            <a href="{{ route('dashboard') }}">COMMAND CENTER ↗</a>
        </nav>
        <section class="error-content" aria-labelledby="error-title">
            <p class="error-code">ERROR // 404 // RECORD UNAVAILABLE</p>
            <h1 id="error-title">DATA<br><em>NOT FOUND</em></h1>
            <p>Intel untuk halaman atau koleksi yang diminta tidak tersedia. Kembali ke katalog untuk memilih data yang masih aktif.</p>
            <div class="error-actions">
                <a href="{{ route('collection.index') }}">EXPLORE COLLECTION ↗</a>
                <a class="secondary" href="{{ route('dashboard') }}">BACK TO COMMAND CENTER</a>
                @if (auth()->user()?->role === 'pustakawan')
                    <a class="secondary" href="{{ route('librarian.index') }}">LIBRARY OPERATIONS</a>
                @endif
            </div>
        </section>
        <footer class="error-footer">STACK // REQUEST TERMINATED WITHOUT DATA LOSS</footer>
    </main>
</body>
</html>
