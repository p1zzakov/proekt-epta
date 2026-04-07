@extends('admin.layouts.app')
@section('title', $mailbox->email . ' — Входящие')
@section('page-title', '✉️ ' . $mailbox->email)

@section('content')

<div style="display:grid;grid-template-columns:200px 1fr;gap:16px;align-items:start;">

    {{-- Сайдбар папок --}}
    <div class="card" style="padding:12px;">
        <a href="{{ route('admin.mail.compose', $mailbox) }}" class="btn btn-primary" style="width:100%;text-align:center;margin-bottom:12px;display:block;text-decoration:none;">
            ✍️ Написать
        </a>

        <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:8px;">Папки</div>

        <a href="{{ route('admin.mail.inbox', $mailbox) }}" style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:6px;text-decoration:none;background:{{ $folder === 'INBOX' ? 'var(--bg3)' : 'transparent' }};color:var(--text);margin-bottom:2px;">
            <span>📥 Входящие</span>
            @if($unread > 0)
                <span style="background:var(--accent);color:#fff;font-size:9px;padding:2px 6px;border-radius:10px;font-weight:700;">{{ $unread }}</span>
            @endif
        </a>

        @foreach($folders as $f)
            @if($f !== 'INBOX')
            <a href="{{ route('admin.mail.inbox', [$mailbox, 'folder' => $f]) }}"
               style="display:block;padding:8px 10px;border-radius:6px;text-decoration:none;background:{{ $folder === $f ? 'var(--bg3)' : 'transparent' }};color:var(--text-muted);margin-bottom:2px;font-size:12px;">
                📁 {{ $f }}
            </a>
            @endif
        @endforeach

        <div style="border-top:1px solid var(--border);margin:12px 0;"></div>
        <a href="{{ route('admin.mail.index') }}" style="display:block;padding:8px 10px;font-size:11px;color:var(--text-muted);text-decoration:none;">
            ← Все ящики
        </a>
    </div>

    {{-- Список писем --}}
    <div class="card" style="padding:0;overflow:hidden;">
        <div style="padding:16px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
            <div style="font-size:12px;color:var(--text-muted);">
                {{ $folder }} — <strong style="color:var(--text)">{{ $messages->count() }}</strong> писем
            </div>
        </div>

        @forelse($messages as $msg)
        <div style="display:grid;grid-template-columns:1fr auto;gap:12px;padding:14px 16px;border-bottom:1px solid var(--border);cursor:pointer;background:{{ !$msg['seen'] ? 'rgba(124,58,237,0.05)' : 'transparent' }};"
             onclick="window.location='{{ route('admin.mail.show', [$mailbox, $msg['uid']]) }}'">
            <div>
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    @if(!$msg['seen'])
                        <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0;"></div>
                    @endif
                    <span style="font-size:12px;font-weight:{{ !$msg['seen'] ? '700' : '400' }};color:var(--text);">
                        {{ $msg['from'] ?: '(нет отправителя)' }}
                    </span>
                </div>
                <div style="font-size:13px;font-weight:{{ !$msg['seen'] ? '600' : '400' }};color:{{ !$msg['seen'] ? 'var(--text)' : 'var(--text-muted)' }};">
                    {{ $msg['subject'] }}
                </div>
            </div>
            <div style="font-size:10px;color:var(--text-muted);white-space:nowrap;text-align:right;">
                {{ \Carbon\Carbon::parse($msg['date'])->diffForHumans() }}
            </div>
        </div>
        @empty
        <div style="text-align:center;color:var(--text-muted);padding:60px;">
            📭 Писем нет
        </div>
        @endforelse
    </div>
</div>

@endsection
