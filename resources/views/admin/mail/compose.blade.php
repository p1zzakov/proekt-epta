@extends('admin.layouts.app')
@section('title', 'Написать письмо')
@section('page-title', '✍️ Написать письмо')

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div style="margin-bottom:16px;font-size:12px;color:var(--text-muted);">
            От: <strong style="color:var(--text)">{{ $mailbox->display_name }}</strong>
        </div>

        <form method="POST" action="{{ route('admin.mail.send', $mailbox) }}">
            @csrf

            <div class="form-group">
                <label>Кому</label>
                <input type="email" name="to" value="{{ preg_replace('/.*<([^>]+)>.*/', '$1', $replyTo ?? old('to')) }}" required placeholder="example@gmail.com">
            </div>

            <div class="form-group">
                <label>Тема</label>
                <input type="text" name="subject" value="{{ $subject ?? old('subject') }}" required placeholder="Тема письма">
            </div>

            <div class="form-group">
                <label>Сообщение</label>
                <textarea name="body" rows="12" required placeholder="Текст письма...">{{ $body ?? old('body') }}</textarea>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary">📤 Отправить</button>
                <a href="{{ route('admin.mail.inbox', $mailbox) }}" class="btn btn-ghost" style="text-decoration:none;">Отмена</a>
            </div>
        </form>
    </div>
</div>

@endsection
