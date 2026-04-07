@extends('admin.layouts.app')
@section('title', 'Почта')
@section('page-title', 'Почта')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div style="color:var(--text-muted);font-size:12px;">
        Ящиков: <strong style="color:var(--text)">{{ $mailboxes->count() }}</strong>
    </div>
    <button class="btn btn-primary" onclick="openModal('modal-create')">+ Создать ящик</button>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;">
    @forelse($mailboxes as $mailbox)
    <div class="card">
        <div class="flex items-center gap-3 mb-3">
            <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;">
                ✉️
            </div>
            <div>
                <div style="font-weight:600;color:var(--text);font-size:13px;">{{ $mailbox->email }}</div>
                @if($mailbox->name)
                    <div style="font-size:11px;color:var(--text-muted);">{{ $mailbox->name }}</div>
                @endif
            </div>
        </div>

        @if($mailbox->note)
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:12px;">{{ $mailbox->note }}</div>
        @endif

        <div class="flex gap-2">
            <a href="{{ route('admin.mail.inbox', $mailbox) }}" class="btn btn-primary" style="flex:1;text-align:center;text-decoration:none;">
                📥 Открыть
            </a>
            <a href="{{ route('admin.mail.compose', $mailbox) }}" class="btn btn-ghost" style="padding:7px 10px;" title="Написать письмо">✍️</a>
            <form method="POST" action="{{ route('admin.mail.destroy', $mailbox) }}" style="margin:0"
                onsubmit="return confirm('Удалить ящик {{ $mailbox->email }}? Все письма будут удалены!')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" style="padding:7px 10px;">🗑️</button>
            </form>
        </div>
    </div>
    @empty
    <div class="card" style="text-align:center;color:var(--text-muted);padding:40px;">
        Нет ящиков — создай первый!
    </div>
    @endforelse
</div>

{{-- Модалка создания --}}
<div class="modal-overlay" id="modal-create">
    <div class="modal">
        <div class="modal-title">Создать почтовый ящик</div>
        <form method="POST" action="{{ route('admin.mail.store') }}">
            @csrf
            <div class="form-group">
                <label>Email адрес</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="support@viewlab.top" required>
            </div>
            <div class="form-group">
                <label>Отображаемое имя</label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="ViewLab Support">
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required minlength="6">
            </div>
            <div class="form-group">
                <label>Заметка</label>
                <input type="text" name="note" placeholder="Основной ящик поддержки">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('modal-create')">Отмена</button>
                <button type="submit" class="btn btn-primary">Создать</button>
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
</script>
@endpush
