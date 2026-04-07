@php $edit = $edit ?? false; @endphp

<div class="grid-2">
    <div class="form-group">
        <label>Название</label>
        <input type="text" name="name" value="{{ old('name') }}" placeholder="Growth" required>
    </div>
    <div class="form-group">
        <label>Slug (системное имя)</label>
        <input type="text" name="slug" value="{{ old('slug') }}" placeholder="growth_month" required
            {{ $edit ? 'readonly style=opacity:0.5' : '' }}>
    </div>
</div>

{{-- Цена и период --}}
<div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px;">
    <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Тарификация</div>
    <div class="grid-2">
        <div class="form-group">
            <label>Цена ($) за единицу</label>
            <input type="number" name="price" value="{{ old('price') }}" step="0.01" min="0" required placeholder="29.00">
        </div>
        <div class="form-group">
            <label>Период</label>
            <select name="billing_period">
                @foreach(['hour'=>'⏱️ Час','day'=>'📅 День','week'=>'📅 Неделя','month'=>'📅 Месяц','stream'=>'🎮 За стрим'] as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="grid-2">
        <div class="form-group">
            <label>Мин. единиц (клиент может купить от)</label>
            <input type="number" name="min_units" value="{{ old('min_units', 1) }}" min="1">
        </div>
        <div class="form-group">
            <label>Макс. единиц (0 = без лимита)</label>
            <input type="number" name="max_units" value="{{ old('max_units', 1) }}" min="0">
        </div>
    </div>
    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">
        💡 Итоговая цена = цена × количество единиц выбранных клиентом
    </div>
</div>

<div class="form-group">
    <label>Описание</label>
    <input type="text" name="description" value="{{ old('description') }}" placeholder="Оплата по часам">
</div>

{{-- Режим ботов --}}
<div class="form-group">
    <label>Режим ботов</label>
    <select name="bot_mode" id="bot_mode_select" onchange="updateBotMode(this.value)">
        <option value="viewers">👁️ Только зрители — без чат-ботов</option>
        <option value="manual">🕹️ Зрители + ручное управление чатом</option>
        <option value="ai">🧠 Зрители + AI чат (боты слушают стримера)</option>
    </select>
    <div id="bot_mode_desc" style="font-size:11px;color:var(--text-muted);margin-top:6px;padding:8px;background:var(--bg3);border-radius:6px;"></div>
</div>

{{-- Лимиты --}}
<div style="background:var(--bg3);border:1px solid var(--border);border-radius:8px;padding:16px;margin-bottom:16px;">
    <div style="font-size:10px;color:var(--text-muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:12px;">Лимиты тарифа</div>
    <div class="grid-2">
        <div class="form-group">
            <label>Макс. зрителей (0 = без лимита)</label>
            <input type="number" name="max_viewers" value="{{ old('max_viewers', 50) }}" min="0">
        </div>
        <div class="form-group" id="max_bots_group">
            <label>Макс. ботов в чате (0 = без лимита)</label>
            <input type="number" name="max_bots" value="{{ old('max_bots', 0) }}" min="0">
        </div>
    </div>
    <div class="grid-2">
        <div class="form-group">
            <label>Одновременных стримов</label>
            <input type="number" name="max_streams" value="{{ old('max_streams', 1) }}" min="1">
        </div>
        <div class="form-group">
            <label>Длительность 1 стрима (часов, 0 = без лимита)</label>
            <input type="number" name="stream_duration" value="{{ old('stream_duration', 4) }}" min="0">
        </div>
    </div>
</div>

<div class="form-group">
    <label>Возможности (каждая с новой строки)</label>
    <textarea name="features" rows="4" placeholder="150 зрителей&#10;20 ботов в чате&#10;AI слушает стримера">{{ old('features') }}</textarea>
</div>

<div class="grid-2">
    <div class="form-group">
        <label>Текст кнопки</label>
        <input type="text" name="button_text" value="{{ old('button_text', 'Выбрать') }}" placeholder="Выбрать">
    </div>
    <div class="form-group">
        <label>Бейдж</label>
        <input type="text" name="badge" value="{{ old('badge') }}" placeholder="Популярный">
    </div>
</div>

<div class="form-group">
    <label>Порядок отображения</label>
    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" style="max-width:100px;">
</div>

<div style="display:flex;gap:20px;margin-bottom:16px;">
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);">
        <input type="checkbox" name="is_popular" value="1" {{ old('is_popular') ? 'checked' : '' }}
            style="width:16px;height:16px;padding:0;accent-color:var(--accent);">
        ⭐ Популярный
    </label>
    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);">
        <input type="checkbox" name="is_active" value="1" checked
            style="width:16px;height:16px;padding:0;accent-color:var(--accent);">
        ✅ Активен
    </label>
</div>

<script>
const modeDescs = {
    viewers: '👁️ Клиент получает только накрутку зрителей. Чат-боты не активны.',
    manual:  '🕹️ Зрители + ручное управление сообщениями ботов в чате через ЛК.',
    ai:      '🧠 Зрители + AI-боты автоматически слушают стримера и отвечают по контексту.',
};
function updateBotMode(val) {
    document.getElementById('bot_mode_desc').textContent = modeDescs[val] || '';
    const g = document.getElementById('max_bots_group');
    g.style.opacity = val === 'viewers' ? '0.4' : '1';
    g.querySelector('input').disabled = val === 'viewers';
}
updateBotMode(document.getElementById('bot_mode_select').value);
</script>