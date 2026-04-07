@extends('admin.layouts.app')
@section('title', 'Настройки')
@section('page-title', 'Настройки системы')

@section('content')

<form method="POST" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')

    @php
        $groups = [
            'general'  => ['icon' => '🌐', 'label' => 'Общие'],
            'ollama'   => ['icon' => '🧠', 'label' => 'Ollama (LLM)'],
            'bot'      => ['icon' => '🤖', 'label' => 'Bot Engine'],
            'twitch'   => ['icon' => '🎮', 'label' => 'Twitch API'],
            'telegram' => ['icon' => '✈️', 'label' => 'Telegram'],
        ];
    @endphp

    <div style="display:grid;gap:20px;">
        @foreach($groups as $groupKey => $groupInfo)
            @if(isset($settings[$groupKey]))
            <div class="card">
                <div class="card-title">{{ $groupInfo['icon'] }} {{ $groupInfo['label'] }}</div>

                <div style="display:grid;gap:16px;">
                    @foreach($settings[$groupKey] as $setting)
                    <div style="display:grid;grid-template-columns:1fr 2fr;gap:16px;align-items:start;padding-bottom:16px;border-bottom:1px solid var(--border);">

                        {{-- Описание --}}
                        <div>
                            <div style="font-size:12px;font-weight:600;color:var(--text);">{{ $setting['label'] }}</div>
                            @if($setting['description'])
                                <div style="font-size:11px;color:var(--text-muted);margin-top:3px;line-height:1.5;">{{ $setting['description'] }}</div>
                            @endif
                            <div style="font-size:9px;color:var(--border);margin-top:4px;letter-spacing:1px;">{{ $setting['key'] }}</div>
                        </div>

                        {{-- Поле ввода --}}
                        <div>
                            @if($setting['type'] === 'boolean')
                                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;text-transform:none;letter-spacing:0;font-size:13px;color:var(--text);">
                                    <input type="checkbox"
                                        name="{{ $setting['key'] }}"
                                        value="1"
                                        {{ $setting['value'] ? 'checked' : '' }}
                                        style="width:18px;height:18px;padding:0;accent-color:var(--accent);">
                                    {{ $setting['value'] ? 'Включено' : 'Выключено' }}
                                </label>

                            @elseif($setting['type'] === 'integer')
                                <input type="number"
                                    name="{{ $setting['key'] }}"
                                    value="{{ $setting['value'] }}"
                                    style="max-width:160px;">

                            @elseif(str_contains($setting['key'], 'secret') || str_contains($setting['key'], 'token') || str_contains($setting['key'], 'password'))
                                {{-- Секретные поля --}}
                                <div style="position:relative;">
                                    <input type="password"
                                        name="{{ $setting['key'] }}"
                                        value="{{ $setting['value'] }}"
                                        id="field-{{ $setting['key'] }}"
                                        autocomplete="off">
                                    <button type="button"
                                        onclick="toggleSecret('field-{{ $setting['key'] }}')"
                                        style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:12px;">
                                        👁️
                                    </button>
                                </div>

                            @else
                                <input type="text"
                                    name="{{ $setting['key'] }}"
                                    value="{{ $setting['value'] }}">
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach
    </div>

    {{-- Сохранить --}}
    <div style="position:sticky;bottom:0;background:var(--bg);padding:16px 0;margin-top:8px;border-top:1px solid var(--border);display:flex;gap:12px;align-items:center;">
        <button type="submit" class="btn btn-primary" style="padding:10px 32px;">
            💾 Сохранить настройки
        </button>
        <div style="font-size:11px;color:var(--text-muted);">
            Изменения применяются сразу без перезапуска
        </div>
    </div>
</form>

{{-- Дополнительно: статус системы --}}
<div class="card mt-4" style="margin-top:20px;">
    <div class="card-title">🔍 Диагностика</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;" id="diag-grid">
        <div style="background:var(--bg3);border-radius:8px;padding:12px;">
            <div style="font-size:10px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px;">Ollama</div>
            <div id="diag-ollama" style="font-size:12px;color:var(--text-muted);">Проверяем...</div>
        </div>
        <div style="background:var(--bg3);border-radius:8px;padding:12px;">
            <div style="font-size:10px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px;">Queue Worker</div>
            <div id="diag-queue" style="font-size:12px;color:var(--text-muted);">Проверяем...</div>
        </div>
        <div style="background:var(--bg3);border-radius:8px;padding:12px;">
            <div style="font-size:10px;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:1px;">Redis</div>
            <div id="diag-redis" style="font-size:12px;color:var(--text-muted);">Проверяем...</div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function toggleSecret(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Диагностика
async function runDiag() {
    try {
        const r = await fetch('/api/status');
        const d = await r.json();

        const ollama = document.getElementById('diag-ollama');
        const redis  = document.getElementById('diag-redis');
        const queue  = document.getElementById('diag-queue');

        if (d.checks.ollama?.ok) {
            ollama.innerHTML = `<span style="color:var(--green)">✅ Online</span><br><span style="color:var(--text-muted);font-size:10px;">${d.model}</span>`;
        } else {
            ollama.innerHTML = `<span style="color:var(--red)">❌ Недоступна</span>`;
        }

        if (d.checks.redis?.ok) {
            redis.innerHTML = `<span style="color:var(--green)">✅ Online</span>`;
        } else {
            redis.innerHTML = `<span style="color:var(--red)">❌ Недоступен</span>`;
        }

        // Queue — проверяем через размер очереди
        if (d.checks.redis?.ok) {
            queue.innerHTML = `<span style="color:var(--green)">✅ Worker активен</span><br><span style="color:var(--text-muted);font-size:10px;">supervisor</span>`;
        } else {
            queue.innerHTML = `<span style="color:var(--yellow)">⚠️ Проверь supervisor</span>`;
        }

    } catch(e) {
        document.getElementById('diag-ollama').innerHTML = `<span style="color:var(--red)">❌ Ошибка</span>`;
    }
}

runDiag();
</script>
@endpush