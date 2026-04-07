@extends('admin.layouts.app')
@section('title', 'Прокси')
@section('page-title', 'Прокси')

@section('content')

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card green">
        <div class="stat-label">Доступно</div>
        <div class="stat-value">{{ $stats['available'] }}</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">Используется</div>
        <div class="stat-value">{{ $stats['in_use'] }}</div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Мёртвых</div>
        <div class="stat-value">{{ $stats['dead'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Всего</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
</div>

<div class="flex gap-2 mb-6">
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ Добавить прокси</button>
    <button class="btn btn-ghost" onclick="openModal('modal-import')">📥 Импорт из TXT</button>
    <button class="btn btn-ghost" id="btn-check-all" onclick="checkAll()">🔄 Проверить все</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Прокси</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Аккаунт</th>
                    <th>Скорость</th>
                    <th>Ошибки</th>
                    <th>Последняя проверка</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proxies as $proxy)
                <tr id="proxy-row-{{ $proxy->id }}">
                    <td>
                        <div style="font-family:monospace;font-size:11px;color:var(--text);">
                            {{ $proxy->host }}:{{ $proxy->port }}
                        </div>
                        @if($proxy->username)
                        <div style="font-size:10px;color:var(--text-muted);">{{ $proxy->username }}:***</div>
                        @endif
                    </td>
                    <td><span class="badge badge-purple">{{ strtoupper($proxy->type) }}</span></td>
                    <td>
                        @php
                            $colors = ['available'=>'green','in_use'=>'yellow','dead'=>'red'];
                            $icons  = ['available'=>'✅','in_use'=>'⏳','dead'=>'💀'];
                        @endphp
                        <span class="badge badge-{{ $colors[$proxy->status] ?? 'purple' }}" id="proxy-status-{{ $proxy->id }}">
                            {{ $icons[$proxy->status] ?? '' }} {{ $proxy->status }}
                        </span>
                    </td>
                    <td style="font-size:11px;color:var(--accent2);">
                        {{ $proxy->account?->username ?? '—' }}
                    </td>
                    <td style="font-size:11px;color:var(--text-muted);" id="proxy-speed-{{ $proxy->id }}">
                        {{ $proxy->response_time_ms ? $proxy->response_time_ms.'ms' : '—' }}
                    </td>
                    <td style="color:{{ $proxy->fail_count > 0 ? 'var(--red)' : 'var(--text-muted)' }}">
                        {{ $proxy->fail_count }}
                    </td>
                    <td style="font-size:11px;color:var(--text-muted);">
                        {{ $proxy->last_checked_at ? $proxy->last_checked_at->diffForHumans() : '—' }}
                    </td>
                    <td>
                        <div style="display:flex;gap:4px;">
                            <button class="btn btn-ghost" style="padding:4px 8px;font-size:10px;"
                                onclick="checkProxy({{ $proxy->id }}, this)" title="Проверить">🔄</button>
                            <form method="POST" action="{{ route('admin.proxies.destroy', $proxy) }}" style="margin:0"
                                onsubmit="return confirm('Удалить прокси?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding:4px 8px;font-size:10px;">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">Нет прокси</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($proxies->hasPages())
    <div style="display:flex;justify-content:center;gap:4px;margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
        @if(!$proxies->onFirstPage())
            <a href="{{ $proxies->previousPageUrl() }}" class="btn btn-ghost">←</a>
        @endif
        @if($proxies->hasMorePages())
            <a href="{{ $proxies->nextPageUrl() }}" class="btn btn-ghost">→</a>
        @endif
    </div>
    @endif
</div>

{{-- Модалка добавления одного прокси --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal">
        <div class="modal-title">Добавить прокси</div>
        <form method="POST" action="{{ route('admin.proxies.store') }}">
            @csrf
            <div class="form-group">
                <label>Тип</label>
                <select name="type">
                    <option value="socks5">SOCKS5</option>
                    <option value="http">HTTP</option>
                    <option value="https">HTTPS</option>
                </select>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Хост</label>
                    <input type="text" name="host" placeholder="192.168.1.1" required>
                </div>
                <div class="form-group">
                    <label>Порт</label>
                    <input type="number" name="port" placeholder="1080" required>
                </div>
            </div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Логин (если есть)</label>
                    <input type="text" name="username" placeholder="user">
                </div>
                <div class="form-group">
                    <label>Пароль (если есть)</label>
                    <input type="text" name="password" placeholder="pass">
                </div>
            </div>
            <div class="form-group">
                <label>Заметка</label>
                <input type="text" name="note" placeholder="IPRoyal #1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>

{{-- Модалка импорта --}}
<div class="modal-overlay" id="modal-import">
    <div class="modal" style="width:520px;">
        <div class="modal-title">Импорт прокси из TXT</div>
        <form method="POST" action="{{ route('admin.proxies.import') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label>Тип по умолчанию</label>
                <select name="type">
                    <option value="socks5">SOCKS5</option>
                    <option value="http">HTTP</option>
                    <option value="https">HTTPS</option>
                </select>
            </div>
            <div class="form-group">
                <label>TXT файл</label>
                <input type="file" name="file" accept=".txt" required>
                <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
                    Поддерживаемые форматы (по одной строке):<br>
                    • <code>host:port</code><br>
                    • <code>host:port:user:pass</code><br>
                    • <code>user:pass@host:port</code><br>
                    • <code>socks5://user:pass@host:port</code>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-import')">Отмена</button>
                <button type="submit" class="btn btn-primary">Импортировать</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

async function checkProxy(id, btn) {
    const orig = btn.textContent;
    btn.textContent = '⏳'; btn.disabled = true;

    try {
        const r = await fetch(`/admin/proxies/${id}/check`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const d = await r.json();

        const status = document.getElementById(`proxy-status-${id}`);
        const speed  = document.getElementById(`proxy-speed-${id}`);

        if (d.alive) {
            status.className = 'badge badge-green';
            status.textContent = '✅ available';
            speed.textContent = d.response_time_ms + 'ms';
            btn.textContent = '✅';
        } else {
            status.className = 'badge badge-red';
            status.textContent = '💀 dead';
            speed.textContent = '—';
            btn.textContent = '❌';
        }
    } catch(e) {
        btn.textContent = orig;
    }

    setTimeout(() => { btn.textContent = orig; btn.disabled = false; }, 2000);
}

async function checkAll() {
    const btn = document.getElementById('btn-check-all');
    btn.textContent = '⏳ Проверяем...'; btn.disabled = true;

    const rows = document.querySelectorAll('tr[id^="proxy-row-"]');
    for (const row of rows) {
        const id = row.id.replace('proxy-row-', '');
        const checkBtn = row.querySelector('button[onclick^="checkProxy"]');
        if (checkBtn) await checkProxy(parseInt(id), checkBtn);
        await new Promise(r => setTimeout(r, 200));
    }

    btn.textContent = '🔄 Проверить все'; btn.disabled = false;
}
</script>
@endpush
