<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ViewLab — Регистрация</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Unbounded:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root { --bg:#0a0a0f;--bg2:#111118;--bg3:#1a1a24;--border:#2a2a3a;--accent:#7c3aed;--accent2:#a855f7;--red:#ef4444;--green:#22c55e;--text:#e2e8f0;--muted:#6b7280; }
        * { box-sizing:border-box;margin:0;padding:0; }
        body { background:var(--bg);font-family:'JetBrains Mono',monospace;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px; }
        body::before { content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(124,58,237,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(124,58,237,0.03) 1px,transparent 1px);background-size:40px 40px;pointer-events:none; }
        .wrap { position:relative;z-index:10;width:480px;animation:fadeUp 0.4s ease; }
        .logo { text-align:center;margin-bottom:28px; }
        .logo-brand { font-family:'Unbounded',sans-serif;font-size:24px;font-weight:800;color:var(--accent2); }
        .logo-sub { font-size:10px;color:var(--muted);letter-spacing:3px;text-transform:uppercase;margin-top:4px; }
        .card { background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:28px;position:relative;overflow:hidden; }
        .card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2),transparent); }
        .card-title { font-family:'Unbounded',sans-serif;font-size:15px;font-weight:600;color:var(--text);margin-bottom:4px; }
        .card-sub { font-size:11px;color:var(--muted);margin-bottom:24px; }
        .grid-2 { display:grid;grid-template-columns:1fr 1fr;gap:12px; }
        .form-group { margin-bottom:14px; }
        label { display:block;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:6px; }
        input, select { width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:9px 12px;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:12px;outline:none;transition:border-color 0.2s; }
        input:focus, select:focus { border-color:var(--accent); }
        input::placeholder { color:var(--muted); }
        select option { background:var(--bg2); }
        .btn { width:100%;padding:12px;background:var(--accent);border:none;border-radius:8px;color:#fff;font-family:'Unbounded',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s; }
        .btn:hover { background:var(--accent2);transform:translateY(-1px);box-shadow:0 8px 25px rgba(124,58,237,0.35); }
        .alert-error { background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px 14px;color:var(--red);font-size:12px;margin-bottom:16px; }
        .footer { text-align:center;margin-top:16px;font-size:11px;color:var(--muted); }
        .footer a { color:var(--accent2);text-decoration:none; }
        @keyframes fadeUp { from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)} }
    </style>
</head>
<body>
<div class="wrap">
    <div class="logo">
        <div style="background:#0a0a16;border-radius:10px;padding:4px 12px;display:inline-block;margin-bottom:8px;">
            <img src="/viewlab_logo.svg" style="height:56px;width:auto;display:block;">
        </div>
        <div class="logo-sub">Регистрация</div>
    </div>
    <div class="card">
        <div class="card-title">Создать аккаунт</div>
        <div class="card-sub">Заполни форму чтобы начать использовать сервис</div>

        @if($errors->any())
            <div class="alert-error">❌ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('client.register.post') }}">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label>Имя / никнейм</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Vasya" required maxlength="64">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="email@example.com" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" placeholder="минимум 8 символов" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Повтор пароля</label>
                    <input type="password" name="password_confirmation" placeholder="повтори пароль" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Twitch канал</label>
                    <input type="text" name="twitch_channel" value="{{ old('twitch_channel') }}" placeholder="your_channel">
                </div>
                <div class="form-group">
                    <label>Telegram (опционально)</label>
                    <input type="text" name="telegram" value="{{ old('telegram') }}" placeholder="@username">
                </div>
            </div>
            <div class="form-group" style="margin-bottom:20px;">
                <button type="submit" class="btn">ЗАРЕГИСТРИРОВАТЬСЯ →</button>
            </div>
        </form>
    </div>
    <div class="footer">
        Уже есть аккаунт? <a href="{{ route('client.login') }}">Войти</a>
    </div>
</div>
</body>
</html>