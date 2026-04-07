<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ViewLab — Вход</title>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Unbounded:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root{--bg:#0a0a0f;--bg2:#111118;--bg3:#1a1a24;--border:#2a2a3a;--accent:#7c3aed;--accent2:#a855f7;--red:#ef4444;--green:#22c55e;--text:#e2e8f0;--muted:#6b7280;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{background:var(--bg);font-family:'JetBrains Mono',monospace;min-height:100vh;display:flex;align-items:center;justify-content:center;}
        body::before{content:'';position:fixed;inset:0;background-image:linear-gradient(rgba(124,58,237,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(124,58,237,0.03) 1px,transparent 1px);background-size:40px 40px;pointer-events:none;}
        .wrap{position:relative;z-index:10;width:380px;animation:fadeUp 0.4s ease;}
        .logo{text-align:center;margin-bottom:28px;}
        .logo-brand{font-family:'Unbounded',sans-serif;font-size:26px;font-weight:800;color:var(--accent2);}
        .logo-sub{font-size:10px;color:var(--muted);letter-spacing:3px;text-transform:uppercase;margin-top:4px;}
        .card{background:var(--bg2);border:1px solid var(--border);border-radius:16px;padding:28px;position:relative;overflow:hidden;}
        .card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--accent),var(--accent2),transparent);}
        .card-title{font-family:'Unbounded',sans-serif;font-size:15px;font-weight:600;color:var(--text);margin-bottom:4px;}
        .card-sub{font-size:11px;color:var(--muted);margin-bottom:24px;}
        .form-group{margin-bottom:14px;}
        label{display:block;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);margin-bottom:6px;}
        .input-wrap{position:relative;}
        .input-icon{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:13px;pointer-events:none;}
        input[type=email],input[type=password]{width:100%;background:var(--bg3);border:1px solid var(--border);border-radius:7px;padding:9px 12px 9px 36px;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:12px;outline:none;transition:border-color 0.2s;}
        input:focus{border-color:var(--accent);}
        input::placeholder{color:var(--muted);}
        .remember{display:flex;align-items:center;gap:8px;margin-bottom:18px;}
        .remember input{width:15px;height:15px;padding:0;accent-color:var(--accent);}
        .remember span{font-size:11px;color:var(--muted);}
        .btn{width:100%;padding:11px;background:var(--accent);border:none;border-radius:8px;color:#fff;font-family:'Unbounded',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all 0.2s;}
        .btn:hover{background:var(--accent2);transform:translateY(-1px);}
        .alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:8px;padding:10px 14px;color:var(--green);font-size:12px;margin-bottom:16px;}
        .alert-error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px 14px;color:var(--red);font-size:12px;margin-bottom:16px;}
        .footer{text-align:center;margin-top:16px;font-size:11px;color:var(--muted);}
        .footer a{color:var(--accent2);text-decoration:none;}
        @keyframes fadeUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="logo">
        <div class="logo-brand">ViewLab</div>
        <div class="logo-sub">Личный кабинет</div>
    </div>
    <div class="card">
        <div class="card-title">Вход в систему</div>
        <div class="card-sub">Введи свои данные чтобы продолжить</div>

        @if(session('success'))
            <div class="alert-success">✅ {{ session('success') }}</div>
        @endif
        @if($errors->any())
            <div class="alert-error">❌ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('client.login.post') }}">
            @csrf
            <div class="form-group">
                <label>Email</label>
                <div class="input-wrap">
                    <span class="input-icon">✉️</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="твой email" required autofocus>
                </div>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <div class="input-wrap">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
            </div>
            <label class="remember">
                <input type="checkbox" name="remember" value="1">
                <span>Запомнить меня</span>
            </label>
            <button type="submit" class="btn">ВОЙТИ →</button>
        </form>
    </div>
    <div class="footer">
        Нет аккаунта? <a href="{{ route('client.register') }}">Зарегистрироваться</a>
    </div>
</div>
</body>
</html>
