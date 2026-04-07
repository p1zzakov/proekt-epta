<!DOCTYPE html>
<html lang="ru" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — ViewLab</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Unbounded:wght@400;600;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg:        #0a0a0f;
            --bg2:       #111118;
            --bg3:       #1a1a24;
            --border:    #2a2a3a;
            --accent:    #7c3aed;
            --accent2:   #a855f7;
            --green:     #22c55e;
            --red:       #ef4444;
            --yellow:    #eab308;
            --text:      #e2e8f0;
            --text-muted:#6b7280;
            --sidebar-w: 240px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--bg2);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 20px 20px 16px;
            border-bottom: 1px solid var(--border);
        }

        .sidebar-logo .brand {
            font-family: 'Unbounded', sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: var(--accent2);
            letter-spacing: -0.5px;
        }

        .sidebar-logo .sub {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 2px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 12px 8px;
            overflow-y: auto;
        }

        .nav-section {
            margin-bottom: 20px;
        }

        .nav-label {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 0 12px;
            margin-bottom: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.15s;
            margin-bottom: 2px;
        }

        .nav-link:hover {
            background: var(--bg3);
            color: var(--text);
        }

        .nav-link.active {
            background: rgba(124, 58, 237, 0.15);
            color: var(--accent2);
            border-left: 2px solid var(--accent);
        }

        .nav-icon { width: 16px; text-align: center; font-size: 14px; }

        .sidebar-footer {
            padding: 12px;
            border-top: 1px solid var(--border);
        }

        /* Статус системы в сайдбаре */
        .sys-status {
            background: var(--bg3);
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 11px;
        }

        .sys-status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .sys-status-row:last-child { margin-bottom: 0; }

        .dot {
            width: 6px; height: 6px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .dot.green { background: var(--green); box-shadow: 0 0 6px var(--green); }
        .dot.red   { background: var(--red);   box-shadow: 0 0 6px var(--red); }
        .dot.yellow{ background: var(--yellow);box-shadow: 0 0 6px var(--yellow); }

        /* ── Main content ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            height: 52px;
            background: var(--bg2);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-title {
            font-family: 'Unbounded', sans-serif;
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .badge {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .badge-green  { background: rgba(34,197,94,.15);  color: var(--green); }
        .badge-red    { background: rgba(239,68,68,.15);   color: var(--red); }
        .badge-yellow { background: rgba(234,179,8,.15);   color: var(--yellow); }
        .badge-purple { background: rgba(168,85,247,.15);  color: var(--accent2); }

        .page-content {
            padding: 24px;
            flex: 1;
        }

        /* ── Cards ── */
        .card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 20px;
        }

        .card-title {
            font-family: 'Unbounded', sans-serif;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 16px;
        }

        /* ── Stats grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--accent);
        }

        .stat-card.green::before { background: var(--green); }
        .stat-card.red::before   { background: var(--red); }
        .stat-card.yellow::before{ background: var(--yellow); }

        .stat-label {
            font-size: 10px;
            color: var(--text-muted);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .stat-value {
            font-family: 'Unbounded', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--text);
            line-height: 1;
        }

        .stat-sub {
            font-size: 10px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ── Table ── */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left;
            padding: 8px 12px;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(42,42,58,0.5);
            font-size: 12px;
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--accent);
            color: #fff;
        }
        .btn-primary:hover { background: var(--accent2); }

        .btn-danger {
            background: rgba(239,68,68,.15);
            color: var(--red);
            border: 1px solid rgba(239,68,68,.3);
        }
        .btn-danger:hover { background: rgba(239,68,68,.25); }

        .btn-ghost {
            background: var(--bg3);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover { color: var(--text); }

        .btn-success {
            background: rgba(34,197,94,.15);
            color: var(--green);
            border: 1px solid rgba(34,197,94,.3);
        }
        .btn-success:hover { background: rgba(34,197,94,.25); }

        /* ── Form ── */
        .form-group { margin-bottom: 16px; }

        label {
            display: block;
            font-size: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            background: var(--bg3);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 12px;
            color: var(--text);
            font-family: 'JetBrains Mono', monospace;
            font-size: 12px;
            outline: none;
            transition: border-color 0.15s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent);
        }

        select option { background: var(--bg2); }

        /* ── Grid helpers ── */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .flex    { display: flex; }
        .items-center { align-items: center; }
        .justify-between { justify-content: space-between; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .mb-4  { margin-bottom: 16px; }
        .mb-6  { margin-bottom: 24px; }
        .mt-4  { margin-top: 16px; }

        /* ── Alert ── */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 12px;
        }
        .alert-success { background: rgba(34,197,94,.1);  border: 1px solid rgba(34,197,94,.3);  color: var(--green); }
        .alert-error   { background: rgba(239,68,68,.1);  border: 1px solid rgba(239,68,68,.3);  color: var(--red); }
        .alert-info    { background: rgba(168,85,247,.1); border: 1px solid rgba(168,85,247,.3); color: var(--accent2); }

        /* ── Modal ── */
        .modal-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,.7);
            z-index: 200;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }

        .modal {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            width: 480px;
            max-width: 95vw;
        }

        .modal-title {
            font-family: 'Unbounded', sans-serif;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--text);
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 4px; height: 4px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

        /* ── Animations ── */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .dot.green { animation: pulse-dot 2s infinite; }
    </style>

    @stack('styles')
</head>
<body>

{{-- Sidebar --}}
<aside class="sidebar">
    <div class="sidebar-logo" style="padding:12px 12px 10px;">
        <svg viewBox="0 0 480 200" xmlns="http://www.w3.org/2000/svg" style="width:100%;height:auto;max-height:64px;">
          <defs>
            <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" style="stop-color:#0a0a12;stop-opacity:0" />
              <stop offset="100%" style="stop-color:#0d0d1f;stop-opacity:0" />
            </linearGradient>
            <radialGradient id="eyeGlow" cx="50%" cy="50%" r="50%">
              <stop offset="0%" style="stop-color:#00f0ff;stop-opacity:0.9" />
              <stop offset="60%" style="stop-color:#0088cc;stop-opacity:0.5" />
              <stop offset="100%" style="stop-color:#0044aa;stop-opacity:0" />
            </radialGradient>
            <linearGradient id="signalGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#00f0ff;stop-opacity:0" />
              <stop offset="50%" style="stop-color:#00f0ff;stop-opacity:1" />
              <stop offset="100%" style="stop-color:#aa00ff;stop-opacity:0.8" />
            </linearGradient>
            <linearGradient id="textGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#ffffff" />
              <stop offset="60%" style="stop-color:#ccefff" />
              <stop offset="100%" style="stop-color:#00f0ff" />
            </linearGradient>
            <linearGradient id="labGrad" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color:#aa00ff" />
              <stop offset="100%" style="stop-color:#ff44aa" />
            </linearGradient>
            <filter id="glow" x="-40%" y="-40%" width="180%" height="180%">
              <feGaussianBlur stdDeviation="4" result="blur" />
              <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
            <filter id="eyeFilter" x="-60%" y="-60%" width="220%" height="220%">
              <feGaussianBlur stdDeviation="6" result="blur"/>
              <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
            <filter id="signalGlow" x="-20%" y="-100%" width="140%" height="300%">
              <feGaussianBlur stdDeviation="2.5" result="blur"/>
              <feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge>
            </filter>
          </defs>
          <g transform="translate(52, 100)" filter="url(#eyeFilter)">
            <ellipse cx="0" cy="0" rx="38" ry="22" fill="none" stroke="#00f0ff" stroke-width="1.8" opacity="0.25"/>
            <path d="M-38,0 Q-19,-30 0,-30 Q19,-30 38,0 Q19,30 0,30 Q-19,30 -38,0 Z" fill="none" stroke="url(#signalGrad)" stroke-width="2"/>
            <circle cx="0" cy="0" r="16" fill="url(#eyeGlow)" opacity="0.6"/>
            <circle cx="0" cy="0" r="14" fill="none" stroke="#00f0ff" stroke-width="1.5" opacity="0.8"/>
            <circle cx="0" cy="0" r="6" fill="#00f0ff" opacity="0.95"/>
            <circle cx="0" cy="0" r="2.5" fill="#0a0a12"/>
            <circle cx="-2" cy="-2" r="1.2" fill="white" opacity="0.8"/>
            <path d="M-8,-26 Q0,-32 8,-26" fill="none" stroke="#aa00ff" stroke-width="1.8" stroke-linecap="round" opacity="0.7"/>
            <path d="M-14,-32 Q0,-44 14,-32" fill="none" stroke="#aa00ff" stroke-width="1.5" stroke-linecap="round" opacity="0.4"/>
          </g>
          <rect x="18" y="133" width="68" height="2" rx="1" fill="url(#signalGrad)" opacity="0.5" filter="url(#signalGlow)"/>
          <text x="110" y="107" font-family="Arial Black, Impact, sans-serif" font-size="52" font-weight="900" fill="url(#textGrad)" letter-spacing="-1" filter="url(#glow)">VIEW</text>
          <text x="110" y="145" font-family="Arial Black, Impact, sans-serif" font-size="28" font-weight="900" fill="url(#labGrad)" letter-spacing="6">LAB</text>
          <circle cx="254" cy="137" r="3" fill="#ff44aa" opacity="0.9"/>
          <text x="262" y="145" font-family="Courier New, monospace" font-size="18" font-weight="400" fill="#ffffff" opacity="0.35" letter-spacing="2">.top</text>
        </svg>
        <div style="font-size:9px;color:#4a4a6a;letter-spacing:2px;text-transform:uppercase;text-align:center;margin-top:4px;">Bot Engine v1.0</div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-label">Главное</div>
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <span class="nav-icon">⚡</span> Dashboard
            </a>
            <a href="{{ route('admin.stream') }}" class="nav-link {{ request()->routeIs('admin.stream*') ? 'active' : '' }}">
                <span class="nav-icon">🎮</span> Стрим
            </a>
            <a href="{{ route('admin.tests.index') }}" class="nav-link {{ request()->routeIs('admin.tests*') ? 'active' : '' }}">
                <span class="nav-icon">🧪</span> Тесты
            </a>
            <a href="{{ route('admin.chat-log') }}" class="nav-link {{ request()->routeIs('admin.chat-log') ? 'active' : '' }}">
                <span class="nav-icon">💬</span> Лог чата
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Боты</div>
            <a href="{{ route('admin.bot-types.index') }}" class="nav-link {{ request()->routeIs('admin.bot-types*') ? 'active' : '' }}">
                <span class="nav-icon">🤖</span> Типы ботов
            </a>
            <a href="{{ route('admin.accounts.index') }}" class="nav-link {{ request()->routeIs('admin.accounts.index') ? 'active' : '' }}">
                <span class="nav-icon">🔑</span> Twitch аккаунты
            </a>
            <a href="{{ route('admin.accounts.import') }}" class="nav-link {{ request()->routeIs('admin.accounts.import*') ? 'active' : '' }}">
                <span class="nav-icon">📥</span> Импорт токенов
            </a>
            <a href="{{ route('admin.proxies.index') }}" class="nav-link {{ request()->routeIs('admin.proxies*') ? 'active' : '' }}">
                <span class="nav-icon">🌐</span> Прокси
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Пользователи</div>
            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <span class="nav-icon">👥</span> База пользователей
            </a>
            <a href="{{ route('admin.broadcast') }}" class="nav-link {{ request()->routeIs('admin.broadcast') ? 'active' : '' }}">
                <span class="nav-icon">📢</span> Рассылка
            </a>
        </div>

        <div class="nav-section">
            <div class="nav-label">Система</div>
            <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans*') ? 'active' : '' }}">
                <span class="nav-icon">💎</span> Тарифы
            </a>
            <a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <span class="nav-icon">⚙️</span> Настройки
            </a>
            <a href="{{ route('admin.mail.index') }}" class="nav-link {{ request()->routeIs('admin.mail*') ? 'active' : '' }}">
                <span class="nav-icon">✉️</span> Почта
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div class="sys-status" id="sys-status">
            <div class="sys-status-row">
                <span><span class="dot green"></span>Ollama</span>
                <span id="status-ollama" style="color:var(--text-muted)">—</span>
            </div>
            <div class="sys-status-row">
                <span><span class="dot green"></span>Redis</span>
                <span id="status-redis" style="color:var(--text-muted)">—</span>
            </div>
            <div class="sys-status-row">
                <span><span class="dot green"></span>БД</span>
                <span id="status-db" style="color:var(--text-muted)">—</span>
            </div>
        </div>
    </div>
</aside>

{{-- Main --}}
<div class="main">
    <div class="topbar">
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
        <div class="topbar-right">
            <span id="topbar-model" class="badge badge-purple">{{ config('bot.ollama_model') }}</span>
            <span id="topbar-bots" class="badge badge-green">— ботов</span>
            <span id="topbar-accounts" class="badge badge-yellow">— аккаунтов</span>
            <form method="POST" action="{{ route('admin.logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="btn btn-ghost" style="padding:4px 10px;font-size:10px;">Выйти</button>
            </form>
        </div>
    </div>

    <div class="page-content">
        @if(session('success'))
            <div class="alert alert-success">✅ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-error">❌ {{ session('error') }}</div>
        @endif

        @yield('content')
    </div>
</div>

<script>
// Подтягиваем статус системы каждые 15 сек
async function fetchStatus() {
    try {
        const r = await fetch('/api/status');
        const d = await r.json();

        const setDot = (el, ok) => {
            const dot = el.previousElementSibling;
            if (dot) {
                dot.className = 'dot ' + (ok ? 'green' : 'red');
            }
        };

        const ollama = document.getElementById('status-ollama');
        const redis  = document.getElementById('status-redis');
        const db     = document.getElementById('status-db');

        if (ollama) {
            ollama.textContent = d.checks.ollama?.ok ? 'ok' : 'down';
            ollama.style.color = d.checks.ollama?.ok ? 'var(--green)' : 'var(--red)';
        }
        if (redis) {
            redis.textContent = d.checks.redis?.ok ? 'ok' : 'down';
            redis.style.color = d.checks.redis?.ok ? 'var(--green)' : 'var(--red)';
        }
        if (db) {
            db.textContent = d.checks.database?.ok ? 'ok' : 'down';
            db.style.color = d.checks.database?.ok ? 'var(--green)' : 'var(--red)';

            const bots = document.getElementById('topbar-bots');
            if (bots && d.checks.bots) {
                bots.textContent = d.checks.bots.available + ' ботов';
            }
        }
    } catch(e) {}
}

// Загружаем аккаунты
async function fetchAccounts() {
    try {
        const r = await fetch('/api/accounts/stats');
        const d = await r.json();
        const el = document.getElementById('topbar-accounts');
        if (el) el.textContent = d.available + ' аккаунтов';
    } catch(e) {}
}

fetchStatus();
fetchAccounts();
setInterval(fetchStatus, 15000);
setInterval(fetchAccounts, 30000);
</script>

@stack('scripts')
</body>
</html>