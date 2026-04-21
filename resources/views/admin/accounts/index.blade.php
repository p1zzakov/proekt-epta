@extends('admin.layouts.app')

@section('title', 'Twitch аккаунты')
@section('page-title', 'Twitch аккаунты')

@section('content')

{{-- Статистика --}}
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="stat-card green">
        <div class="stat-label">Доступно</div>
        <div class="stat-value">{{ $stats['available'] }}</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Занято</div>
        <div class="stat-value">{{ $stats['busy'] + $stats['cooldown'] }}</div>
        <div class="stat-sub">{{ $stats['busy'] }} busy / {{ $stats['cooldown'] }} cooldown</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Забанено</div>
        <div class="stat-value">{{ $stats['banned'] }}</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Невалидных</div>
        <div class="stat-value">{{ $stats['invalid'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Всего</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
        <div class="stat-sub">Сегодня: {{ $stats['messages_today'] }} msg</div>
    </div>
</div>

{{-- Вкладки типов --}}
<div class="card mb-4" style="padding:8px 16px;">
    <div style="display:flex;gap:8px;align-items:center;">
        @foreach([''=>'🗂️ Все', 'viewer'=>'👁️ Зрители', 'chatbot'=>'💬 Чат-боты'] as $val => $label)
            <a href="{{ route('admin.accounts.index', array_merge(request()->except('type','page'), $val ? ['type'=>$val] : [])) }}"
               class="btn {{ request('type', '') === $val ? 'btn-primary' : 'btn-ghost' }}"
               style="font-size:13px;">
               {{ $label }}
            </a>
        @endforeach
        <span style="color:var(--text-muted);font-size:11px;margin-left:8px;" id="type-counts">
            Зрители: <strong id="cnt-viewers">{{ \App\Models\Account::where('type','viewer')->count() }}</strong>
            &nbsp;|&nbsp;
            Чат-боты: <strong id="cnt-chatbots">{{ \App\Models\Account::where('type','chatbot')->count() }}</strong>
        </span>
    </div>
</div>

{{-- Фильтры --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.accounts.index') }}">
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
        <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label>Поиск</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="username, note...">
            </div>
            <div style="width:160px;">
                <label>Статус</label>
                <select name="status">
                    <option value="">Все статусы</option>
                    @foreach(['available'=>'✅ Доступен','busy'=>'⏳ Занят','cooldown'=>'⏱️ Cooldown','banned'=>'🚫 Забанен','invalid'=>'❌ Невалиден'] as $val => $label)
                        <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:160px;">
                <label>Телефон</label>
                <select name="phone">
                    <option value="">Все</option>
                    <option value="1" {{ request('phone') === '1' ? 'selected' : '' }}>✅ Верифицирован</option>
                    <option value="0" {{ request('phone') === '0' ? 'selected' : '' }}>❌ Не верифицирован</option>
                </select>
            </div>
            <div style="width:180px;">
                <label>Сортировка</label>
                <select name="sort">
                    @foreach(['username'=>'По имени','messages_sent'=>'По сообщениям','messages_today'=>'Сегодня','last_used_at'=>'Последнее использование','created_at'=>'Дате добавления'] as $val => $label)
                        <option value="{{ $val }}" {{ request('sort', 'created_at') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:120px;">
                <label>Порядок</label>
                <select name="dir">
                    <option value="desc" {{ request('dir', 'desc') === 'desc' ? 'selected' : '' }}>↓ По убыванию</option>
                    <option value="asc"  {{ request('dir') === 'asc'  ? 'selected' : '' }}>↑ По возрастанию</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-ghost">🔍 Фильтр</button>
                <a href="{{ route('admin.accounts.index') }}" class="btn btn-ghost">✕</a>
            </div>
        </div>
    </form>
</div>

{{-- Таблица --}}
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <div style="color:var(--text-muted);font-size:12px;">
            Найдено: <strong style="color:var(--text)">{{ $accounts->total() }}</strong>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-ghost" onclick="validateAll()" id="btn-validate">
                🔄 Проверить все токены
            </button>
            <button class="btn btn-ghost" onclick="checkAllPhones()" id="btn-check-phones">
                📱 Проверить все телефоны
            </button>
            <button class="btn btn-primary" onclick="openModal('modal-create')">+ Добавить</button>
        </div>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Аккаунт</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Телефон</th>
                    <th>Сегодня</th>
                    <th>Всего</th>
                    <th>Последний раз</th>
                    <th>Заметка</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                <tr id="row-{{ $account->id }}">
                    <td>
                        <strong style="color:var(--text)">{{ $account->username }}</strong>
                        <div style="color:var(--text-muted);font-size:10px;">{{ $account->twitch_id ?? '—' }}</div>
                    </td>
                    <td>
                        @if($account->type === 'chatbot')
                            <span class="badge badge-purple">💬 чат-бот</span>
                        @else
                            <span class="badge badge-yellow">👁️ зритель</span>
                        @endif
                    </td>
                    <td>
                        @php
                            $badges = ['available'=>'green','busy'=>'yellow','cooldown'=>'yellow','banned'=>'red','invalid'=>'red'];
                            $icons  = ['available'=>'✅','busy'=>'⏳','cooldown'=>'⏱️','banned'=>'🚫','invalid'=>'❌'];
                        @endphp
                        <span class="badge badge-{{ $badges[$account->status] ?? 'purple' }}">
                            {{ $icons[$account->status] ?? '' }} {{ $account->status }}
                        </span>
                    </td>
                    <td id="phone-{{ $account->id }}">
                        @if($account->phone_verified)
                            <span class="badge badge-green">✅ да</span>
                        @else
                            <span class="badge badge-red">❌ нет</span>
                        @endif
                    </td>
                    <td style="color:{{ $account->messages_today > 0 ? 'var(--green)' : 'var(--text-muted)' }}">
                        {{ $account->messages_today }}
                    </td>
                    <td style="color:var(--text-muted)">{{ number_format($account->messages_sent) }}</td>
                    <td style="color:var(--text-muted);font-size:11px;">
                        {{ $account->last_used_at ? $account->last_used_at->diffForHumans() : '—' }}
                    </td>
                    <td style="color:var(--text-muted);font-size:11px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $account->note ?? '—' }}
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;flex-wrap:wrap;">
                            {{-- Проверить телефон --}}
                            <button class="btn btn-ghost" style="padding:4px 8px;font-size:10px;"
                                onclick="checkPhone({{ $account->id }}, this)"
                                title="Проверить телефон">📱</button>

                            {{-- Проверить токен --}}
                            <button class="btn btn-ghost" style="padding:4px 8px;font-size:10px;"
                                onclick="validateToken({{ $account->id }}, this)"
                                title="Проверить токен">🔄</button>

                            {{-- Редактировать --}}
                            <button class="btn btn-ghost" style="padding:4px 8px;font-size:10px;"
                                onclick="openEdit({{ $account->id }}, '{{ $account->username }}', '{{ addslashes($account->note ?? '') }}', '{{ $account->type }}')"
                                title="Редактировать">✏️</button>

                            {{-- Сброс статуса --}}
                            @if(in_array($account->status, ['busy','cooldown','banned','invalid']))
                            <form method="POST" action="{{ route('admin.accounts.reset', $account) }}" style="margin:0">
                                @csrf
                                <button type="submit" class="btn btn-ghost" style="padding:4px 8px;font-size:10px;" title="Сбросить статус">♻️</button>
                            </form>
                            @endif

                            {{-- Удалить --}}
                            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" style="margin:0"
                                onsubmit="return confirm('Удалить аккаунт {{ $account->username }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding:4px 8px;font-size:10px;">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align:center;color:var(--text-muted);padding:40px;">
                        Нет аккаунтов
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Пагинация --}}
    @if($accounts->hasPages())
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
        <div style="font-size:11px;color:var(--text-muted);">
            Показано {{ $accounts->firstItem() }}–{{ $accounts->lastItem() }} из {{ $accounts->total() }}
        </div>
        <div style="display:flex;gap:4px;">
            @if($accounts->onFirstPage())
                <span class="btn btn-ghost" style="opacity:0.4;cursor:default;">← Назад</span>
            @else
                <a href="{{ $accounts->previousPageUrl() }}&{{ http_build_query(request()->except('page')) }}" class="btn btn-ghost">← Назад</a>
            @endif
            @foreach($accounts->getUrlRange(max(1, $accounts->currentPage()-2), min($accounts->lastPage(), $accounts->currentPage()+2)) as $page => $url)
                @if($page === $accounts->currentPage())
                    <span class="btn btn-primary" style="cursor:default;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="btn btn-ghost">{{ $page }}</a>
                @endif
            @endforeach
            @if($accounts->hasMorePages())
                <a href="{{ $accounts->nextPageUrl() }}&{{ http_build_query(request()->except('page')) }}" class="btn btn-ghost">Вперёд →</a>
            @else
                <span class="btn btn-ghost" style="opacity:0.4;cursor:default;">Вперёд →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Модалка добавления --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal">
        <div class="modal-title">Добавить аккаунт</div>
        <form method="POST" action="{{ route('admin.accounts.store') }}">
            @csrf
            <div class="form-group">
                <label>Username (Twitch логин)</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="twitch_username" required maxlength="64">
            </div>
            <div class="form-group">
                <label>Access Token</label>
                <input type="text" name="access_token" placeholder="oauth:xxxxxxxxxxxxxxxx" required>
            </div>
            <div class="form-group">
                <label>Refresh Token (опционально)</label>
                <input type="text" name="refresh_token" placeholder="Для автообновления токена">
            </div>
            <div class="form-group">
                <label>Тип аккаунта</label>
                <select name="type">
                    <option value="viewer">👁️ Зритель</option>
                    <option value="chatbot">💬 Чат-бот</option>
                </select>
            </div>
            <div class="form-group">
                <label>Заметка</label>
                <input type="text" name="note" value="{{ old('note') }}" placeholder="куплен 27.03, фарм акк #1...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>

{{-- Модалка редактирования --}}
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-title">Редактировать аккаунт</div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            <div class="form-group">
                <label>Username</label>
                <input type="text" id="edit-username" style="opacity:0.5" readonly>
            </div>
            <div class="form-group">
                <label>Тип аккаунта</label>
                <select name="type" id="edit-type">
                    <option value="viewer">👁️ Зритель</option>
                    <option value="chatbot">💬 Чат-бот</option>
                </select>
            </div>
            <div class="form-group">
                <label>Новый Access Token (оставь пустым если не меняешь)</label>
                <input type="text" name="access_token" placeholder="oauth:xxxxxxxxxxxxxxxx">
            </div>
            <div class="form-group">
                <label>Заметка</label>
                <input type="text" name="note" id="edit-note" placeholder="...">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit')">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function openEdit(id, username, note, type) {
    const form = document.getElementById('edit-form');
    form.action = `/admin/accounts/${id}`;
    document.getElementById('edit-username').value = username;
    document.getElementById('edit-note').value = note;
    document.getElementById('edit-type').value = type || 'viewer';
    openModal('modal-edit');
}

// Проверка телефона — используем открытый канал surprise011
async function checkPhone(id, btn) {
    const original = btn.textContent;
    btn.textContent = '⏳';
    btn.disabled = true;

    // surprise011 — открытый канал без followers-only, подходит для проверки
    const channel = 'surprise011';

    try {
        const r = await fetch(`/admin/accounts/${id}/check-phone`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json'},
            body: JSON.stringify({channel})
        });
        const d = await r.json();

        const phoneEl = document.getElementById(`phone-${id}`);
        if (d.phone_verified) {
            phoneEl.innerHTML = '<span class="badge badge-green">✅ да</span>';
            btn.title = 'Телефон верифицирован';
        } else {
            const label = d.status === 'needs_follow' ? '👤 подписка' : '❌ нет';
            phoneEl.innerHTML = `<span class="badge badge-red">${label}</span>`;
            btn.title = d.status;
        }

        btn.textContent = d.phone_verified ? '✅' : '❌';
        setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 2000);
    } catch(e) {
        btn.textContent = original;
        btn.disabled = false;
    }
}

// Массовая проверка телефонов через серверный эндпоинт
async function checkAllPhones() {
    const btn = document.getElementById('btn-check-phones');
    const limit = prompt('Сколько аккаунтов проверить? (по 0.5 сек каждый)', '50');
    if (!limit) return;

    btn.textContent = `⏳ Проверяем ${limit} аккаунтов...`;
    btn.disabled = true;

    try {
        const r = await fetch('{{ route("admin.accounts.bulk-phone-check") }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json'},
            body: JSON.stringify({limit: parseInt(limit), channel: 'surprise011'})
        });
        const d = await r.json();

        btn.textContent = `✅ Готово! ✅${d.results.ok || 0} 📵${d.results.needs_phone || 0}`;
        // Обновляем счётчики вкладок
        setTimeout(() => {
            btn.textContent = '📱 Проверить все телефоны';
            btn.disabled = false;
            location.reload();
        }, 3000);
    } catch(e) {
        btn.textContent = '📱 Проверить все телефоны';
        btn.disabled = false;
    }
}

// Проверка токена
async function validateToken(id, btn) {
    const original = btn.textContent;
    btn.textContent = '⏳';
    btn.disabled = true;

    try {
        const r = await fetch(`/admin/accounts/${id}/validate`, {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json'}
        });
        const d = await r.json();

        const row = document.getElementById(`row-${id}`);
        const badge = row.querySelector('.badge');
        if (d.valid) {
            badge.className = 'badge badge-green';
            badge.textContent = '✅ available';
        } else {
            badge.className = 'badge badge-red';
            badge.textContent = '❌ invalid';
        }
        btn.textContent = d.valid ? '✅' : '❌';
        setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 2000);
    } catch(e) {
        btn.textContent = original;
        btn.disabled = false;
    }
}

// Проверка всех токенов
async function validateAll() {
    const btn = document.getElementById('btn-validate');
    btn.textContent = '⏳ Проверяем...';
    btn.disabled = true;

    const rows = document.querySelectorAll('tr[id^="row-"]');
    for (const row of rows) {
        const id = row.id.replace('row-', '');
        const validateBtn = row.querySelector('button[onclick^="validateToken"]');
        if (validateBtn) await validateToken(parseInt(id), validateBtn);
        await new Promise(r => setTimeout(r, 300));
    }

    btn.textContent = '✅ Готово!';
    setTimeout(() => { btn.textContent = '🔄 Проверить все токены'; btn.disabled = false; }, 2000);
}

@if($errors->any())
    openModal('modal-create');
@endif

// Автообновление счётчиков зрители/чат-боты каждые 10 сек
async function refreshTypeCounts() {
    try {
        const r = await fetch('{{ route("admin.accounts.index") }}?counts_only=1', {
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const d = await r.json();
        if (d.viewers !== undefined) {
            document.getElementById('cnt-viewers').textContent  = d.viewers;
            document.getElementById('cnt-chatbots').textContent = d.chatbots;
        }
    } catch(e) {}
}
setInterval(refreshTypeCounts, 10000);
</script>
@endpush