@extends('admin.layouts.app')
@section('title', $message['subject'])
@section('page-title', '✉️ Письмо')

@section('content')

<div style="max-width:800px;">
    <div class="card" style="margin-bottom:16px;">
        {{-- Шапка письма --}}
        <div style="border-bottom:1px solid var(--border);padding-bottom:16px;margin-bottom:16px;">
            <h2 style="font-size:18px;font-weight:700;color:var(--text);margin-bottom:12px;">{{ $message['subject'] }}</h2>
            <div style="display:grid;gap:6px;">
                <div style="font-size:12px;">
                    <span style="color:var(--text-muted);">От:</span>
                    <strong style="color:var(--text);margin-left:8px;">{{ $message['from'] }}</strong>
                </div>
                <div style="font-size:12px;">
                    <span style="color:var(--text-muted);">Кому:</span>
                    <span style="color:var(--text);margin-left:8px;">{{ $message['to'] }}</span>
                </div>
                @if($message['cc'])
                <div style="font-size:12px;">
                    <span style="color:var(--text-muted);">CC:</span>
                    <span style="color:var(--text);margin-left:8px;">{{ $message['cc'] }}</span>
                </div>
                @endif
                <div style="font-size:11px;color:var(--text-muted);">{{ $message['date'] }}</div>
            </div>
        </div>

        {{-- Тело письма --}}
        <div style="font-size:13px;line-height:1.7;color:var(--text);">
            {!! $message['body'] !!}
        </div>
    </div>

    {{-- Действия --}}
    <div style="display:flex;gap:8px;">
        <a href="{{ route('admin.mail.compose', ['mailbox' => $mailbox, 'reply_to' => $message['from'], 'uid' => $message['uid'], 'subject' => 'Re: '.$message['subject']]) }}"
           class="btn btn-primary" style="text-decoration:none;">
            ↩️ Ответить
        </a>
        <a href="{{ route('admin.mail.inbox', $mailbox) }}" class="btn btn-ghost" style="text-decoration:none;">
            ← Назад
        </a>
        <form method="POST" action="{{ route('admin.mail.delete', [$mailbox, $message['uid']]) }}" style="margin:0;margin-left:auto;"
            onsubmit="return confirm('Удалить письмо?')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-danger">🗑️ Удалить</button>
        </form>
    </div>
</div>

@endsection
