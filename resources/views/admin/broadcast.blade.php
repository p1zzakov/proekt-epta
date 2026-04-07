@extends('admin.layouts.app')
@section('title', 'Рассылка')
@section('page-title', 'Рассылка')

@section('content')

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card">
        <div class="stat-label">Всего рассылок</div>
        <div class="stat-value">{{ $stats['total'] }}</div>
    </div>
    <div class="stat-card yellow">
        <div class="stat-label">В процессе</div>
        <div class="stat-value">{{ $stats['sending'] }}</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Завершено</div>
        <div class="stat-value">{{ $stats['done'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Активных клиентов</div>
        <div class="stat-value">{{ $stats['clients'] }}</div>
        <div class="stat-sub">Возможных получателей</div>
    </div>
</div>

<div class="grid-2 mb-6" style="gap:20px;align-items:start;">

    {{-- Форма создания --}}
    <div class="card">
        <div class="card-title">Новая рассылка</div>

        <form method="POST" action="{{ route('admin.broadcast.store') }}" id="broadcast-form">
            @csrf

            <div class="form-group">
                <label>Тема / заголовок</label>
                <input type="text" name="title" value="{{ old('title') }}" placeholder="Обновление сервиса ViewLab" required maxlength="255">
            </div>

            <div class="form-group">
                <label>Сообщение</label>
                <textarea name="message" rows="5" required placeholder="Текст рассылки...">{{ old('message') }}</textarea>
            </div>

            {{-- Каналы --}}
            <div class="form-group">
                <label>Каналы отправки</label>
                <div style="display:flex;gap:16px;flex-wrap:wrap;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);cursor:pointer;">
                        <input type="checkbox" name="send_push" value="1" checked style="width:15px;height:15px;padding:0;accent-color:var(--accent);">
                        🔔 Push в ЛК
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);cursor:pointer;">
                        <input type="checkbox" name="send_email" value="1" style="width:15px;height:15px;padding:0;accent-color:var(--accent);">
                        ✉️ Email
                    </label>
                    <label style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);cursor:pointer;">
                        <input type="checkbox" name="send_telegram" value="1" style="width:15px;height:15px;padding:0;accent-color:var(--accent);">
                        ✈️ Telegram
                    </label>
                </div>
            </div>

            {{-- Аудитория --}}
            <div class="form-group">
                <label>Аудитория</label>
                <select name="audience" id="audience-select" onchange="updateAudience(this.value)">
                    <option value="all">👥 Все активные клиенты</option>
                    <option value="plan">⭐ По тарифу</option>
                    <option value="status">🔘 По статусу</option>
                    <option value="manual">✋ Выбранные вручную</option>
                </select>
            </div>

            {{-- Доп. фильтр тариф --}}
            <div class="form-group" id="filter-plan" style="display:none;">
                <label>Тариф</label>
                <select name="audience_plan">
                    @foreach(['free'=>'Free','basic'=>'Basic','pro'=>'Pro','enterprise'=>'Enterprise'] as $v => $l)
                        <option value="{{ $v }}">{{ $l }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Доп. фильтр статус --}}
            <div class="form-group" id="filter-status" style="display:none;">
                <label>Статус</label>
                <select name="audience_status">
                    <option value="active">✅ Активные</option>
                    <option value="suspended">⏸️ Приостановленные</option>
                    <option value="banned">🚫 Забаненные</option>
                </select>
            </div>

            {{-- Ручной выбор --}}
            <div class="form-group" id="filter-manual" style="display:none;">
                <label>Выберите клиентов</label>
                <div style="background:var(--bg3);border:1px solid var(--border);border-radius:6px;max-height:160px;overflow-y:auto;padding:8px;">
                    @foreach($clients as $client)
                    <label style="display:flex;align-items:center;gap:8px;padding:4px 0;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);cursor:pointer;">
                        <input type="checkbox" name="audience_ids[]" value="{{ $client->id }}"
                            style="width:14px;height:14px;padding:0;accent-color:var(--accent);"
                            onchange="updateManualCount()">
                        {{ $client->name }}
                        <span style="color:var(--text-muted);font-size:10px;">{{ $client->email }}</span>
                        <span class="badge badge-purple" style="font-size:9px;padding:1px 5px;">{{ $client->plan }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Превью аудитории --}}
            <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:12px;margin-bottom:16px;">
                <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Получателей</div>
                <div id="recipients-count" style="font-family:'Unbounded',sans-serif;font-size:24px;font-weight:800;color:var(--accent2);">
                    {{ $stats['clients'] }}
                </div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;"
                onclick="return confirm('Запустить рассылку? Это действие нельзя отменить.')">
                📢 Запустить рассылку
            </button>
        </form>
    </div>

    {{-- История рассылок --}}
    <div class="card">
        <div class="card-title">История рассылок</div>

        @forelse($broadcasts as $broadcast)
        <div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:14px;margin-bottom:10px;">
            <div class="flex items-center justify-between" style="margin-bottom:8px;">
                <div style="font-weight:600;color:var(--text);font-size:13px;">{{ $broadcast->title }}</div>
                @php
                    $statusColors = ['draft'=>'purple','sending'=>'yellow','done'=>'green','failed'=>'red'];
                    $statusIcons  = ['draft'=>'📝','sending'=>'⏳','done'=>'✅','failed'=>'❌'];
                @endphp
                <span class="badge badge-{{ $statusColors[$broadcast->status] ?? 'purple' }}">
                    {{ $statusIcons[$broadcast->status] ?? '' }} {{ $broadcast->status }}
                </span>
            </div>

            <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;line-height:1.5;">
                {{ Str::limit($broadcast->message, 80) }}
            </div>

            <div style="display:flex;gap:12px;font-size:10px;color:var(--text-muted);flex-wrap:wrap;">
                <span>{{ $broadcast->audience_label }}</span>
                <span>{{ $broadcast->channels }}</span>
                @if($broadcast->status === 'done')
                    <span style="color:var(--green)">✅ {{ $broadcast->sent_count }}</span>
                    @if($broadcast->failed_count > 0)
                        <span style="color:var(--red)">❌ {{ $broadcast->failed_count }}</span>
                    @endif
                @endif
                <span>{{ $broadcast->created_at->format('d.m.Y H:i') }}</span>
            </div>

            @if($broadcast->status === 'sending')
            <div style="margin-top:8px;">
                <div style="height:3px;background:var(--border);border-radius:2px;">
                    @php $pct = $broadcast->total_recipients > 0 ? round($broadcast->sent_count / $broadcast->total_recipients * 100) : 0; @endphp
                    <div style="height:100%;width:{{ $pct }}%;background:var(--accent);border-radius:2px;transition:width 1s;"></div>
                </div>
                <div style="font-size:10px;color:var(--text-muted);margin-top:3px;">{{ $broadcast->sent_count }} / {{ $broadcast->total_recipients }}</div>
            </div>
            @endif
        </div>
        @empty
        <div style="text-align:center;color:var(--text-muted);padding:40px 0;">
            Рассылок ещё не было
        </div>
        @endforelse

        @if($broadcasts->hasPages())
        <div style="display:flex;justify-content:center;gap:4px;margin-top:12px;">
            @if(!$broadcasts->onFirstPage())
                <a href="{{ $broadcasts->previousPageUrl() }}" class="btn btn-ghost">←</a>
            @endif
            @if($broadcasts->hasMorePages())
                <a href="{{ $broadcasts->nextPageUrl() }}" class="btn btn-ghost">→</a>
            @endif
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function updateAudience(val) {
    document.getElementById('filter-plan').style.display   = val === 'plan'   ? 'block' : 'none';
    document.getElementById('filter-status').style.display = val === 'status' ? 'block' : 'none';
    document.getElementById('filter-manual').style.display = val === 'manual' ? 'block' : 'none';

    if (val !== 'manual') fetchCount();
}

function updateManualCount() {
    const checked = document.querySelectorAll('input[name="audience_ids[]"]:checked').length;
    document.getElementById('recipients-count').textContent = checked;
}

async function fetchCount() {
    const form     = document.getElementById('broadcast-form');
    const audience = form.querySelector('[name=audience]').value;
    const plan     = form.querySelector('[name=audience_plan]')?.value;
    const status   = form.querySelector('[name=audience_status]')?.value;

    try {
        const params = new URLSearchParams({ audience, audience_plan: plan, audience_status: status });
        const r = await fetch(`/admin/broadcast/preview?${params}`);
        const d = await r.json();
        document.getElementById('recipients-count').textContent = d.count;
    } catch(e) {}
}

// При смене тарифа/статуса — обновляем счётчик
document.querySelector('[name=audience_plan]')?.addEventListener('change', fetchCount);
document.querySelector('[name=audience_status]')?.addEventListener('change', fetchCount);

// Автообновление прогресса если есть активные рассылки
@if($stats['sending'] > 0)
setInterval(() => location.reload(), 5000);
@endif
</script>
@endpush