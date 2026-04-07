@extends('admin.layouts.app')
@section('title', 'Импорт аккаунтов')
@section('page-title', 'Импорт аккаунтов')

@section('content')

<div class="grid-2" style="align-items:start;gap:20px;">
    <div class="card">
        <div class="card-title">Импорт токенов из TXT</div>

        <form method="POST" action="{{ route('admin.accounts.import') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label>TXT файл с токенами</label>
                <input type="file" name="file" accept=".txt" required>
                <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
                    Один токен на строку — просто GQL токен
                </div>
            </div>

            <div class="form-group">
                <label>Автоматически назначить прокси</label>
                <select name="assign_proxies">
                    <option value="1">✅ Да — назначить свободные прокси</option>
                    <option value="0">❌ Нет — без прокси</option>
                </select>
                <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">
                    Доступно прокси: <strong style="color:var(--green)">{{ $availableProxies }}</strong>
                </div>
            </div>

            <div class="form-group">
                <label>Заметка (опционально)</label>
                <input type="text" name="note" placeholder="Закупка 30.03.2026">
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;">
                📥 Импортировать токены
            </button>
        </form>
    </div>

    <div class="card">
        <div class="card-title">Инструкция</div>
        <div style="font-size:12px;color:var(--text-muted);line-height:1.8;">
            <div style="margin-bottom:12px;">
                <strong style="color:var(--text);">Формат TXT файла:</strong><br>
                Каждый токен на отдельной строке:
            </div>
            <div style="background:var(--bg3);border-radius:6px;padding:12px;font-family:monospace;font-size:11px;margin-bottom:16px;">
                eajxrw1eni7dob678z1p5k34zvhbh0<br>
                abc123def456ghi789jkl012mno345<br>
                xyz789uvw456rst123opq012nml345<br>
                ...
            </div>
            <div style="margin-bottom:8px;">
                <strong style="color:var(--text);">Что происходит при импорте:</strong>
            </div>
            <div>✅ Каждый токен проверяется через GQL</div>
            <div>✅ Валидные — добавляются в пул</div>
            <div>❌ Невалидные — помечаются как invalid</div>
            <div>🔑 Username подтягивается автоматически</div>
            <div>🌐 Прокси назначается автоматически (если выбрано)</div>
        </div>

        @if(session('import_result'))
        <div style="margin-top:16px;background:var(--bg3);border-radius:8px;padding:12px;">
            <div style="font-size:11px;color:var(--text-muted);margin-bottom:8px;text-transform:uppercase;letter-spacing:1px;">Результат последнего импорта</div>
            @php $result = session('import_result'); @endphp
            <div style="color:var(--green);">✅ Добавлено: {{ $result['added'] }}</div>
            <div style="color:var(--red);">❌ Невалидных: {{ $result['invalid'] }}</div>
            <div style="color:var(--yellow);">⚠️ Дубликатов: {{ $result['duplicates'] }}</div>
        </div>
        @endif
    </div>
</div>

@endsection
