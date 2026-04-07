@extends('admin.layouts.app')
@section('title', 'Тарифы')
@section('page-title', 'Тарифы')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div style="color:var(--text-muted);font-size:12px;">
        Тарифов: <strong style="color:var(--text)">{{ $plans->count() }}</strong>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ Новый тариф</button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px;">
    @foreach($plans as $plan)
    <div class="card" style="position:relative;{{ $plan->is_popular ? 'border-color:var(--accent);' : '' }}">

        @if($plan->badge)
        <div style="position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--accent);color:#fff;font-size:10px;font-weight:700;padding:4px 14px;border-radius:20px;white-space:nowrap;font-family:'Unbounded',sans-serif;">
            {{ $plan->badge }}
        </div>
        @endif

        <div class="flex items-center justify-between mb-4">
            <div>
                <div style="font-family:'Unbounded',sans-serif;font-size:14px;font-weight:700;color:var(--accent2);">{{ $plan->name }}</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">{{ $plan->description }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-family:'Unbounded',sans-serif;font-size:28px;font-weight:800;color:var(--text);">${{ number_format($plan->price, 0) }}</div>
                <div style="font-size:10px;color:var(--text-muted);">/ {{ $plan->period }}</div>
            </div>
        </div>

        {{-- Режим ботов --}}
        <div style="background:var(--bg3);border-radius:6px;padding:8px 12px;margin-bottom:12px;font-size:12px;color:var(--text);">
            {{ $plan->getBotModeLabel() }}
        </div>

        {{-- Лимиты --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px;">
            <div style="background:var(--bg3);border-radius:6px;padding:8px;text-align:center;">
                <div style="font-family:'Unbounded',sans-serif;font-size:18px;font-weight:700;color:var(--text);">{{ $plan->getMaxViewersLabel() }}</div>
                <div style="font-size:10px;color:var(--text-muted);">зрителей</div>
            </div>
            <div style="background:var(--bg3);border-radius:6px;padding:8px;text-align:center;opacity:{{ $plan->bot_mode === 'viewers' ? '0.4' : '1' }}">
                <div style="font-family:'Unbounded',sans-serif;font-size:18px;font-weight:700;color:var(--text);">{{ $plan->getMaxBotsLabel() }}</div>
                <div style="font-size:10px;color:var(--text-muted);">ботов</div>
            </div>
            <div style="background:var(--bg3);border-radius:6px;padding:8px;text-align:center;">
                <div style="font-family:'Unbounded',sans-serif;font-size:18px;font-weight:700;color:var(--text);">{{ $plan->max_streams }}</div>
                <div style="font-size:10px;color:var(--text-muted);">стримов</div>
            </div>
            <div style="background:var(--bg3);border-radius:6px;padding:8px;text-align:center;">
                <div style="font-family:'Unbounded',sans-serif;font-size:18px;font-weight:700;color:var(--text);">{{ $plan->stream_duration === 0 ? '∞' : $plan->stream_duration.'ч' }}</div>
                <div style="font-size:10px;color:var(--text-muted);">длительность</div>
            </div>
        </div>

        {{-- Фичи --}}
        <div style="margin-bottom:12px;">
            @foreach($plan->features as $feature)
            <div style="display:flex;align-items:center;gap:8px;padding:3px 0;font-size:11px;color:var(--text-muted);">
                <span style="color:var(--green);font-weight:700;">✓</span>{{ $feature }}
            </div>
            @endforeach
        </div>

        {{-- Статус --}}
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">
            @if($plan->is_active)
                <span class="badge badge-green">active</span>
            @else
                <span class="badge badge-red">hidden</span>
            @endif
            <span style="font-size:10px;color:var(--text-muted);">Порядок: {{ $plan->sort_order }}</span>
            @if($plan->is_popular)
                <span class="badge badge-purple">popular</span>
            @endif
        </div>

        <div class="flex gap-2">
            <button class="btn btn-ghost" style="flex:1;justify-content:center;"
                onclick="openEdit({{ $plan->id }})">✏️ Редактировать</button>
            <form method="POST" action="{{ route('admin.plans.destroy', $plan) }}" style="margin:0"
                onsubmit="return confirm('Удалить тариф {{ $plan->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" style="padding:7px 10px;">🗑️</button>
            </form>
        </div>
    </div>
    @endforeach
</div>

{{-- Модалки --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal" style="width:580px;max-height:90vh;overflow-y:auto;">
        <div class="modal-title">Новый тариф</div>
        <form method="POST" action="{{ route('admin.plans.store') }}">
            @csrf
            @include('admin.plans._form')
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modal-edit">
    <div class="modal" style="width:580px;max-height:90vh;overflow-y:auto;">
        <div class="modal-title">Редактировать тариф</div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            @include('admin.plans._form', ['edit' => true])
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
const plans = @json($plans->keyBy('id'));

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function openEdit(id) {
    const p = plans[id];
    if (!p) return;
    const form = document.getElementById('edit-form');
    form.action = `/admin/plans/${id}`;

    form.querySelector('[name=name]').value            = p.name;
    form.querySelector('[name=slug]').value            = p.slug;
    form.querySelector('[name=price]').value           = p.price;
    form.querySelector('[name=period]').value          = p.period;
    form.querySelector('[name=description]').value     = p.description || '';
    form.querySelector('[name=features]').value        = (p.features || []).join('\n');
    form.querySelector('[name=button_text]').value     = p.button_text || 'Начать';
    form.querySelector('[name=badge]').value           = p.badge || '';
    form.querySelector('[name=sort_order]').value      = p.sort_order;
    form.querySelector('[name=max_viewers]').value     = p.max_viewers;
    form.querySelector('[name=max_bots]').value        = p.max_bots;
    form.querySelector('[name=max_streams]').value     = p.max_streams;
    form.querySelector('[name=stream_duration]').value = p.stream_duration;
    form.querySelector('[name=is_popular]').checked    = p.is_popular;
    form.querySelector('[name=is_active]').checked     = p.is_active;

    const modeSelect = form.querySelector('[name=bot_mode]');
    modeSelect.value = p.bot_mode;
    // Триггерим обновление описания
    if (typeof updateBotMode === 'function') updateBotMode(p.bot_mode);

    openModal('modal-edit');
}

@if($errors->any())
    openModal('modal-create');
@endif
</script>
@endpush
