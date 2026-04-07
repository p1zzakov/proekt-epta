@extends('admin.layouts.app')

@section('title', 'Типы ботов')
@section('page-title', 'Типы ботов')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div style="color:var(--text-muted);font-size:12px;">
        Типов: <strong style="color:var(--text)">{{ $types->count() }}</strong>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ Новый тип</button>
</div>

<div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px;">
    @foreach($types as $type)
    <div class="card" style="position:relative;">

        {{-- Статус --}}
        <div style="position:absolute;top:16px;right:16px;">
            @if($type->is_active)
                <span class="badge badge-green">active</span>
            @else
                <span class="badge badge-red">off</span>
            @endif
        </div>

        {{-- Заголовок --}}
        <div style="margin-bottom:12px;">
            <div style="font-family:'Unbounded',sans-serif;font-size:15px;font-weight:700;color:var(--text);">
                {{ $type->label }}
            </div>
            <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;margin-top:2px;">
                {{ $type->name }}
            </div>
        </div>

        {{-- Промпт --}}
        <div style="
            background:var(--bg3);
            border:1px solid var(--border);
            border-radius:6px;
            padding:10px 12px;
            font-size:11px;
            color:var(--text-muted);
            line-height:1.6;
            margin-bottom:12px;
            max-height:80px;
            overflow:hidden;
            position:relative;
        ">
            {{ Str::limit($type->system_prompt, 120) }}
        </div>

        {{-- Эмоуты --}}
        @if(!empty($type->emotes))
        <div style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:4px;">
            @foreach(array_slice($type->emotes, 0, 6) as $emote)
                <span style="background:rgba(124,58,237,0.1);border:1px solid rgba(124,58,237,0.2);border-radius:4px;padding:2px 6px;font-size:10px;color:var(--accent2);">{{ $emote }}</span>
            @endforeach
            @if(count($type->emotes) > 6)
                <span style="font-size:10px;color:var(--text-muted);">+{{ count($type->emotes) - 6 }}</span>
            @endif
        </div>
        @endif

        {{-- Использование --}}
        <div style="font-size:10px;color:var(--text-muted);margin-bottom:12px;">
            Ботов с этим типом: <strong style="color:var(--text)">{{ $type->bots()->count() }}</strong>
        </div>

        {{-- Кнопки --}}
        <div class="flex gap-2">
            <button class="btn btn-ghost" style="flex:1;justify-content:center;"
                onclick="openEdit({{ $type->id }})">✏️ Редактировать</button>

            @if($type->bots()->count() === 0)
            <form method="POST" action="{{ route('admin.bot-types.destroy', $type) }}" style="margin:0"
                onsubmit="return confirm('Удалить тип {{ $type->label }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" style="padding:7px 10px;">🗑️</button>
            </form>
            @endif
        </div>
    </div>
    @endforeach
</div>

{{-- Модалка создания --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal" style="width:600px;max-height:90vh;overflow-y:auto;">
        <div class="modal-title">Новый тип бота</div>
        <form method="POST" action="{{ route('admin.bot-types.store') }}">
            @csrf
            @include('admin.bot-types._form')
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать</button>
            </div>
        </form>
    </div>
</div>

{{-- Модалка редактирования --}}
<div class="modal-overlay" id="modal-edit">
    <div class="modal" style="width:600px;max-height:90vh;overflow-y:auto;">
        <div class="modal-title">Редактировать тип</div>
        <form method="POST" id="edit-form" action="">
            @csrf @method('PUT')
            @include('admin.bot-types._form', ['edit' => true])
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-edit')">Отмена</button>
                <button type="submit" class="btn btn-primary">Сохранить</button>
            </div>
        </form>
    </div>
</div>

{{-- JSON данные для JS --}}
<script>
const botTypes = @json($types->keyBy('id'));

function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

function openEdit(id) {
    const type = botTypes[id];
    if (!type) return;

    const form = document.getElementById('edit-form');
    form.action = `/admin/bot-types/${id}`;

    form.querySelector('[name=name]').value              = type.name;
    form.querySelector('[name=label]').value             = type.label;
    form.querySelector('[name=system_prompt]').value     = type.system_prompt;
    form.querySelector('[name=behavior_prompt]').value   = type.behavior_prompt;
    form.querySelector('[name=emoji_instruction]').value = type.emoji_instruction;
    form.querySelector('[name=emotes]').value            = (type.emotes || []).join(', ');
    form.querySelector('[name=emoji]').value             = (type.emoji || []).join(', ');
    form.querySelector('[name=ru_words]').value          = (type.ru_words || []).join(', ');
    form.querySelector('[name=is_active]').checked       = type.is_active;

    openModal('modal-edit');
}

@if($errors->any())
    openModal('modal-create');
@endif
</script>

@endsection
