@extends('admin.layouts.app')

@section('title', 'Управление ботами')
@section('page-title', 'Управление ботами')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div style="color:var(--text-muted);font-size:12px;">
        Всего ботов: <strong style="color:var(--text)">{{ $bots->count() }}</strong>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ Создать бота</button>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Стиль</th>
                    <th>Темы</th>
                    <th>Токсичность</th>
                    <th>Активность</th>
                    <th>Вес</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bots as $bot)
                <tr>
                    <td>
                        <strong style="color:var(--text)">{{ $bot->name }}</strong>
                    </td>
                    <td><span class="badge badge-purple">{{ $bot->style }}</span></td>
                    <td style="color:var(--text-muted);font-size:11px;">
                        {{ !empty($bot->knowledge) ? implode(', ', $bot->knowledge) : '—' }}
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:60px;height:4px;background:var(--bg3);border-radius:2px;">
                                <div style="width:{{ $bot->toxicity * 100 }}%;height:100%;background:{{ $bot->toxicity > 0.6 ? 'var(--red)' : ($bot->toxicity > 0.3 ? 'var(--yellow)' : 'var(--green)') }};border-radius:2px;"></div>
                            </div>
                            <span style="font-size:10px;color:var(--text-muted)">{{ round($bot->toxicity * 100) }}%</span>
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <div style="width:60px;height:4px;background:var(--bg3);border-radius:2px;">
                                <div style="width:{{ $bot->verbosity * 100 }}%;height:100%;background:var(--accent);border-radius:2px;"></div>
                            </div>
                            <span style="font-size:10px;color:var(--text-muted)">{{ round($bot->verbosity * 100) }}%</span>
                        </div>
                    </td>
                    <td>{{ $bot->weight }}</td>
                    <td>
                        @if($bot->cooldown_until && $bot->cooldown_until->isFuture())
                            <span class="badge badge-yellow">cooldown</span>
                        @else
                            <span class="badge badge-green">ready</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-ghost"
                                onclick="openEdit({{ $bot->id }}, '{{ $bot->name }}', '{{ $bot->style }}', {{ json_encode($bot->knowledge ?? []) }}, {{ $bot->toxicity }}, {{ $bot->verbosity }}, {{ $bot->weight }})"
                                style="padding:4px 8px;font-size:10px;">✏️</button>

                            @if($bot->cooldown_until && $bot->cooldown_until->isFuture())
                            <form method="POST" action="{{ route('admin.bots.reset-cooldown', $bot) }}" style="margin:0">
                                @csrf
                                <button type="submit" class="btn btn-ghost" style="padding:4px 8px;font-size:10px;" title="Сбросить кулдаун">⏱️</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('admin.bots.destroy', $bot) }}" style="margin:0"
                                onsubmit="return confirm('Удалить бота {{ $bot->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger" style="padding:4px 8px;font-size:10px;">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;color:var(--text-muted);padding:40px;">
                        Нет ботов — создай первого!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Модалка создания --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal">
        <div class="modal-title">Создать бота</div>
        <form method="POST" action="{{ route('admin.bots.store') }}">
            @csrf
            @include('admin.bots._form')
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать</button>
            </div>
        </form>
    </div>
</div>

{{-- Модалка редактирования --}}
<div class="modal-overlay" id="modal-edit">
    <div class="modal">
        <div class="modal-title">Редактировать бота</div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            @include('admin.bots._form', ['edit' => true])
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
function openModal(id) {
    document.getElementById(id).classList.add('open');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

// Закрытие по клику на оверлей
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

function openEdit(id, name, style, knowledge, toxicity, verbosity, weight) {
    const form = document.getElementById('edit-form');
    form.action = `/admin/bots/${id}`;

    form.querySelector('[name=name]').value       = name;
    form.querySelector('[name=style]').value      = style;
    form.querySelector('[name=toxicity]').value   = toxicity;
    form.querySelector('[name=verbosity]').value  = verbosity;
    form.querySelector('[name=weight]').value     = weight;

    // Обновляем слайдеры
    updateSlider('edit-toxicity',  toxicity);
    updateSlider('edit-verbosity', verbosity);

    // Knowledge
    form.querySelector('[name=knowledge]').value = knowledge.join(', ');

    openModal('modal-edit');
}

function updateSlider(id, val) {
    const el = document.getElementById(id);
    if (el) {
        el.textContent = Math.round(val * 100) + '%';
    }
}

// Слайдеры
document.querySelectorAll('input[type=range]').forEach(input => {
    const display = document.getElementById(input.dataset.display);
    if (display) {
        input.addEventListener('input', () => {
            display.textContent = Math.round(input.value * 100) + '%';
        });
    }
});

@if($errors->any())
    openModal('modal-create');
@endif
</script>
@endpush