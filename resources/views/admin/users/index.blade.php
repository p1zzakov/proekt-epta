@extends('admin.layouts.app')
@section('title', 'База пользователей')
@section('page-title', 'База пользователей')

@section('content')

{{-- Статистика --}}
<div class="stats-grid" style="grid-template-columns:repeat(5,1fr);">
    <div class="stat-card green">
        <div class="stat-label">Всего клиентов</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Активных</div>
        <div class="stat-value">{{ $stats['active'] }}</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">С тарифом</div>
        <div class="stat-value">{{ $stats['with_plan'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Общий баланс</div>
        <div class="stat-value" style="font-size:20px;">${{ number_format($stats['total_balance'], 2) }}</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Новых сегодня</div>
        <div class="stat-value">{{ $stats['new_today'] }}</div>
    </div>
</div>

{{-- Фильтры --}}
<div class="card mb-6">
    <form method="GET" action="{{ route('admin.users.index') }}">
        <div style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div style="flex:1;min-width:200px;">
                <label>Поиск</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="имя, email, telegram, twitch...">
            </div>
            <div style="width:150px;">
                <label>Статус</label>
                <select name="status">
                    <option value="">Все</option>
                    @foreach(['active'=>'✅ Активен','suspended'=>'⏸️ Приостановлен','banned'=>'🚫 Забанен'] as $v => $l)
                        <option value="{{ $v }}" {{ request('status') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:150px;">
                <label>Тариф</label>
                <select name="plan">
                    <option value="">Все тарифы</option>
                    @foreach(['free'=>'Free','basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise'] as $v => $l)
                        <option value="{{ $v }}" {{ request('plan') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:180px;">
                <label>Сортировка</label>
                <select name="sort">
                    @foreach(['created_at'=>'Дате регистрации','balance'=>'Балансу','name'=>'Имени','last_login_at'=>'Последнему входу'] as $v => $l)
                        <option value="{{ $v }}" {{ request('sort','created_at') === $v ? 'selected' : '' }}>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div style="width:130px;">
                <label>Порядок</label>
                <select name="dir">
                    <option value="desc" {{ request('dir','desc') === 'desc' ? 'selected' : '' }}>↓ Убывание</option>
                    <option value="asc"  {{ request('dir') === 'asc' ? 'selected' : '' }}>↑ Возрастание</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-ghost">🔍</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">✕</a>
            </div>
        </div>
    </form>
</div>

{{-- Таблица --}}
<div class="card">
    <div class="flex items-center justify-between mb-4">
        <div style="color:var(--text-muted);font-size:12px;">
            Найдено: <strong style="color:var(--text)">{{ $clients->total() }}</strong>
        </div>
        <button class="btn btn-primary" onclick="openModal('modal-create')">+ Добавить клиента</button>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Клиент</th>
                    <th>Контакты</th>
                    <th>Twitch</th>
                    <th>Тариф</th>
                    <th>Баланс</th>
                    <th>Статус</th>
                    <th>Регистрация</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text)">{{ $client->name }}</div>
                        <div style="font-size:10px;color:var(--text-muted)">{{ $client->email }}</div>
                    </td>
                    <td style="font-size:11px;color:var(--text-muted)">
                        @if($client->telegram)
                            <div>✈️ {{ $client->telegram }}</div>
                        @endif
                    </td>
                    <td style="font-size:11px;color:var(--accent2)">
                        {{ $client->twitch_channel ?? '—' }}
                    </td>
                    <td>
                        @php
                            $planColors = ['free'=>'purple','basic'=>'green','pro'=>'yellow','enterprise'=>'green'];
                        @endphp
                        <div>
                            <span class="badge badge-{{ $planColors[$client->plan] ?? 'purple' }}">
                                {{ $client->plan_label }}
                            </span>
                            @if($client->plan !== 'free' && $client->plan_expires_at)
                                <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">
                                    до {{ $client->plan_expires_at->format('d.m.Y') }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600;color:{{ $client->balance > 0 ? 'var(--green)' : 'var(--text-muted)' }}">
                            ${{ number_format($client->balance, 2) }}
                        </div>
                    </td>
                    <td>
                        @php
                            $statusColors = ['active'=>'green','suspended'=>'yellow','banned'=>'red'];
                            $statusIcons  = ['active'=>'✅','suspended'=>'⏸️','banned'=>'🚫'];
                        @endphp
                        <span class="badge badge-{{ $statusColors[$client->status] ?? 'purple' }}">
                            {{ $statusIcons[$client->status] ?? '' }} {{ $client->status }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--text-muted)">
                        {{ $client->created_at->format('d.m.Y') }}
                        @if($client->last_login_at)
                            <div style="font-size:10px;">{{ $client->last_login_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            {{-- Просмотр --}}
                            <button class="btn btn-ghost" style="padding:4px 8px;font-size:10px;"
                                onclick="openView({{ $client->id }})">👁️</button>

                            {{-- Пополнить баланс --}}
                            <button class="btn btn-success" style="padding:4px 8px;font-size:10px;"
                                onclick="openDeposit({{ $client->id }}, '{{ $client->name }}', {{ $client->balance }})">💰</button>

                            {{-- Редактировать --}}
                            <button class="btn btn-ghost" style="padding:4px 8px;font-size:10px;"
                                onclick="openEdit({{ $client->id }})">✏️</button>

                            {{-- Бан/разбан --}}
                            <form method="POST" action="{{ route('admin.users.toggle', $client) }}" style="margin:0">
                                @csrf
                                <button type="submit" class="btn btn-{{ $client->status === 'banned' ? 'success' : 'danger' }}"
                                    style="padding:4px 8px;font-size:10px;"
                                    title="{{ $client->status === 'banned' ? 'Разбанить' : 'Забанить' }}">
                                    {{ $client->status === 'banned' ? '♻️' : '🚫' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">
                        Нет клиентов
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Пагинация --}}
    @if($clients->hasPages())
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
        <div style="font-size:11px;color:var(--text-muted);">
            {{ $clients->firstItem() }}–{{ $clients->lastItem() }} из {{ $clients->total() }}
        </div>
        <div style="display:flex;gap:4px;">
            @if($clients->onFirstPage())
                <span class="btn btn-ghost" style="opacity:0.4;cursor:default;">← Назад</span>
            @else
                <a href="{{ $clients->previousPageUrl() }}" class="btn btn-ghost">← Назад</a>
            @endif
            @foreach($clients->getUrlRange(max(1,$clients->currentPage()-2), min($clients->lastPage(),$clients->currentPage()+2)) as $page => $url)
                @if($page === $clients->currentPage())
                    <span class="btn btn-primary" style="cursor:default;">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="btn btn-ghost">{{ $page }}</a>
                @endif
            @endforeach
            @if($clients->hasMorePages())
                <a href="{{ $clients->nextPageUrl() }}" class="btn btn-ghost">Вперёд →</a>
            @else
                <span class="btn btn-ghost" style="opacity:0.4;cursor:default;">Вперёд →</span>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- Модалка создания --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal" style="width:520px;">
        <div class="modal-title">Добавить клиента</div>
        <form method="POST" action="{{ route('admin.users.store') }}">
            @csrf
            <div class="grid-2">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="64">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required minlength="8">
                </div>
                <div class="form-group">
                    <label>Telegram</label>
                    <input type="text" name="telegram" value="{{ old('telegram') }}" placeholder="@username">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Twitch канал</label>
                    <input type="text" name="twitch_channel" value="{{ old('twitch_channel') }}" placeholder="channel_name">
                </div>
                <div class="form-group">
                    <label>Тариф</label>
                    <select name="plan">
                        @foreach(['free'=>'Free','basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise'] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Заметки</label>
                <textarea name="notes" rows="2" placeholder="Внутренние заметки..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать</button>
            </div>
        </form>
    </div>
</div>

{{-- Модалка пополнения баланса --}}
<div class="modal-overlay" id="modal-deposit">
    <div class="modal">
        <div class="modal-title">Пополнить баланс</div>
        <form method="POST" id="deposit-form" action="">
            @csrf
            <div style="margin-bottom:16px;padding:12px;background:var(--bg3);border-radius:8px;">
                <div style="font-size:11px;color:var(--text-muted);">Клиент</div>
                <div id="deposit-name" style="font-weight:600;color:var(--text);margin-top:2px;"></div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Текущий баланс:
                    <strong id="deposit-balance" style="color:var(--green)"></strong>
                </div>
            </div>
            <div class="form-group">
                <label>Тип операции</label>
                <select name="type">
                    <option value="deposit">💰 Пополнение</option>
                    <option value="bonus">🎁 Бонус</option>
                    <option value="refund">↩️ Возврат</option>
                    <option value="withdraw">💸 Списание</option>
                </select>
            </div>
            <div class="form-group">
                <label>Сумма ($)</label>
                <input type="number" name="amount" min="0.01" step="0.01" required placeholder="10.00">
            </div>
            <div class="form-group">
                <label>Описание</label>
                <input type="text" name="description" placeholder="Оплата тарифа Pro...">
            </div>
            <div class="form-group">
                <label>Номер транзакции (опционально)</label>
                <input type="text" name="reference" placeholder="TXN-12345">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-deposit')">Отмена</button>
                <button type="submit" class="btn btn-success">Провести</button>
            </div>
        </form>
    </div>
</div>

{{-- Модалка просмотра --}}
<div class="modal-overlay" id="modal-view">
    <div class="modal" style="width:560px;">
        <div class="modal-title">Информация о клиенте</div>
        <div id="view-content" style="color:var(--text-muted);font-size:12px;">Загрузка...</div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('modal-view')">Закрыть</button>
        </div>
    </div>
</div>

{{-- Модалка редактирования --}}
<div class="modal-overlay" id="modal-edit">
    <div class="modal" style="width:520px;">
        <div class="modal-title">Редактировать клиента</div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            <div class="grid-2">
                <div class="form-group">
                    <label>Имя</label>
                    <input type="text" name="name" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label>Telegram</label>
                    <input type="text" name="telegram" id="edit-telegram" placeholder="@username">
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Twitch канал</label>
                    <input type="text" name="twitch_channel" id="edit-twitch">
                </div>
                <div class="form-group">
                    <label>Тариф</label>
                    <select name="plan" id="edit-plan">
                        @foreach(['free'=>'Free','basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise'] as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Срок тарифа (оставь пустым = бессрочно)</label>
                <input type="datetime-local" name="plan_expires_at" id="edit-expires">
            </div>
            <div class="form-group">
                <label>Новый пароль (оставь пустым если не меняешь)</label>
                <input type="password" name="password" minlength="8">
            </div>
            <div class="form-group">
                <label>Заметки</label>
                <textarea name="notes" id="edit-notes" rows="2"></textarea>
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
const clients = @json($clients->keyBy('id'));

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function openDeposit(id, name, balance) {
    document.getElementById('deposit-form').action = `/admin/users/${id}/deposit`;
    document.getElementById('deposit-name').textContent = name;
    document.getElementById('deposit-balance').textContent = '$' + parseFloat(balance).toFixed(2);
    openModal('modal-deposit');
}

function openEdit(id) {
    const c = clients[id];
    if (!c) return;
    document.getElementById('edit-form').action = `/admin/users/${id}`;
    document.getElementById('edit-name').value     = c.name;
    document.getElementById('edit-telegram').value = c.telegram || '';
    document.getElementById('edit-twitch').value   = c.twitch_channel || '';
    document.getElementById('edit-plan').value     = c.plan;
    document.getElementById('edit-notes').value    = c.notes || '';
    if (c.plan_expires_at) {
        document.getElementById('edit-expires').value = c.plan_expires_at.replace(' ', 'T').substring(0, 16);
    }
    openModal('modal-edit');
}

async function openView(id) {
    openModal('modal-view');
    document.getElementById('view-content').innerHTML = '⏳ Загрузка...';
    try {
        const r = await fetch(`/admin/users/${id}/info`);
        const d = await r.json();
        const c = d.client;
        const txs = d.transactions;

        let txHtml = txs.length ? txs.map(t => `
            <tr>
                <td style="color:var(--text-muted)">${new Date(t.created_at).toLocaleDateString('ru')}</td>
                <td>${t.type === 'deposit' || t.type === 'bonus' || t.type === 'refund'
                    ? '<span style="color:var(--green)">+$' + parseFloat(t.amount).toFixed(2) + '</span>'
                    : '<span style="color:var(--red)">-$' + parseFloat(t.amount).toFixed(2) + '</span>'}</td>
                <td style="color:var(--text-muted)">${t.description || '—'}</td>
                <td style="color:var(--text-muted)">$${parseFloat(t.balance_after).toFixed(2)}</td>
            </tr>
        `).join('') : '<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:16px;">Нет транзакций</td></tr>';

        document.getElementById('view-content').innerHTML = `
            <div class="grid-2" style="margin-bottom:16px;">
                <div style="background:var(--bg3);border-radius:8px;padding:12px;">
                    <div style="font-size:10px;color:var(--text-muted);margin-bottom:8px;letter-spacing:1px;text-transform:uppercase;">Данные</div>
                    <div><strong style="color:var(--text)">${c.name}</strong></div>
                    <div style="color:var(--text-muted)">${c.email}</div>
                    ${c.telegram ? `<div style="color:var(--text-muted)">✈️ ${c.telegram}</div>` : ''}
                    ${c.twitch_channel ? `<div style="color:var(--accent2)">🎮 ${c.twitch_channel}</div>` : ''}
                </div>
                <div style="background:var(--bg3);border-radius:8px;padding:12px;">
                    <div style="font-size:10px;color:var(--text-muted);margin-bottom:8px;letter-spacing:1px;text-transform:uppercase;">Финансы</div>
                    <div style="font-size:24px;font-weight:800;color:var(--green)">$${parseFloat(c.balance).toFixed(2)}</div>
                    <div style="color:var(--text-muted);margin-top:4px;">Тариф: ${c.plan}</div>
                    ${c.notes ? `<div style="color:var(--text-muted);margin-top:4px;font-size:11px;">📝 ${c.notes}</div>` : ''}
                </div>
            </div>
            <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">История транзакций</div>
            <div style="max-height:200px;overflow-y:auto;">
                <table style="width:100%">
                    <thead><tr>
                        <th>Дата</th><th>Сумма</th><th>Описание</th><th>Баланс</th>
                    </tr></thead>
                    <tbody>${txHtml}</tbody>
                </table>
            </div>
        `;
    } catch(e) {
        document.getElementById('view-content').innerHTML = '❌ Ошибка загрузки';
    }
}

@if($errors->any())
    openModal('modal-create');
@endif
</script>
@endpush