@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')

<div class="stats-grid">
    <div class="stat-card green">
        <div class="stat-label">Активных ботов</div>
        <div class="stat-value" id="stat-bots">{{ $stats['bots']['available'] }}</div>
        <div class="stat-sub">из {{ $stats['bots']['total'] }} всего</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Аккаунтов Twitch</div>
        <div class="stat-value" id="stat-accounts">{{ $stats['accounts']['available'] }}</div>
        <div class="stat-sub">доступно / {{ $stats['accounts']['total'] }} всего</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Сообщений сегодня</div>
        <div class="stat-value">{{ $stats['messages_today'] }}</div>
        <div class="stat-sub">всего: {{ $stats['messages_total'] }}</div>
    </div>
    <div class="stat-card {{ $stats['ollama_ok'] ? 'green' : 'red' }}">
        <div class="stat-label">Ollama</div>
        <div class="stat-value" style="font-size:18px;">{{ $stats['ollama_ok'] ? 'ONLINE' : 'OFFLINE' }}</div>
        <div class="stat-sub">{{ config('bot.ollama_model') }}</div>
    </div>
</div>

<div class="grid-2 mb-6">
    {{-- Боты --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <div class="card-title">Боты</div>
            <a href="{{ route('admin.bots.index') }}" class="btn btn-primary">+ Добавить</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Имя</th>
                        <th>Стиль</th>
                        <th>Статус</th>
                        <th>Вес</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bots as $bot)
                    <tr>
                        <td>{{ $bot->name }}</td>
                        <td><span class="badge badge-purple">{{ $bot->style }}</span></td>
                        <td>
                            @if($bot->cooldown_until && $bot->cooldown_until->isFuture())
                                <span class="badge badge-yellow">cooldown</span>
                            @else
                                <span class="badge badge-green">ready</span>
                            @endif
                        </td>
                        <td>{{ $bot->weight }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="color:var(--text-muted);text-align:center;padding:20px;">Нет ботов</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Аккаунты --}}
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <div class="card-title">Twitch аккаунты</div>
            <a href="{{ route('admin.accounts.index') }}" class="btn btn-ghost">Все</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Аккаунт</th>
                        <th>Статус</th>
                        <th>Сегодня</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                    <tr>
                        <td>{{ $account->username }}</td>
                        <td>
                            @php
                                $colors = ['available'=>'green','busy'=>'yellow','cooldown'=>'yellow','banned'=>'red','invalid'=>'red'];
                                $color = $colors[$account->status] ?? 'purple';
                            @endphp
                            <span class="badge badge-{{ $color }}">{{ $account->status }}</span>
                        </td>
                        <td>{{ $account->messages_today }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="color:var(--text-muted);text-align:center;padding:20px;">Нет аккаунтов</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Быстрый тест --}}
<div class="card">
    <div class="card-title">Быстрый тест бота</div>
    <div class="grid-2">
        <div>
            <div class="form-group">
                <label>Фраза стримера</label>
                <input type="text" id="test-phrase" value="ребят норм билд?" placeholder="Что сказал стример...">
            </div>
            <div class="form-group">
                <label>Игра (опционально)</label>
                <input type="text" id="test-game" placeholder="Dota 2, CS2...">
            </div>
            <button class="btn btn-primary" onclick="testBot()">▶ Отправить</button>
        </div>
        <div>
            <label>Ответ бота</label>
            <div id="test-result" style="
                background: var(--bg3);
                border: 1px solid var(--border);
                border-radius: 6px;
                padding: 12px;
                min-height: 80px;
                font-size: 13px;
                color: var(--text-muted);
            ">Нажми ▶ чтобы проверить...</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
async function testBot() {
    const phrase = document.getElementById('test-phrase').value;
    const game   = document.getElementById('test-game').value;
    const result = document.getElementById('test-result');

    result.style.color = 'var(--text-muted)';
    result.textContent = '⏳ Отправляем в Ollama...';

    try {
        const r = await fetch('/api/respond', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ channel: 'test', text: phrase, game: game || null })
        });
        const d = await r.json();

        if (d.responded) {
            result.style.color = 'var(--text)';
            result.innerHTML = `<strong style="color:var(--accent2)">${d.bot.name}</strong> [${d.bot.style}]<br><br>${d.message}`;
        } else {
            result.style.color = 'var(--red)';
            result.textContent = '❌ ' + (d.message || d.reason);
        }
    } catch(e) {
        result.style.color = 'var(--red)';
        result.textContent = '❌ Ошибка: ' + e.message;
    }
}
</script>
@endpush
