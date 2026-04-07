@php $edit = $edit ?? false; @endphp

<div class="form-group">
    <label>Имя бота</label>
    <input type="text" name="name" value="{{ old('name') }}" placeholder="Vasya_Sarcastic" required maxlength="64">
</div>

<div class="form-group">
    <label>Стиль</label>
    <select name="style" required>
        @foreach(['sarcastic'=>'😏 Саркастик','hype'=>'🔥 Хайп','toxic'=>'😤 Токсик','silent'=>'🤐 Молчун','memer'=>'💀 Мемщик','analyst'=>'🧠 Аналитик','noob'=>'😳 Нубас','veteran'=>'😑 Старожил','hater'=>'🙄 Хейтер','neutral'=>'😐 Нейтральный'] as $val => $label)
            <option value="{{ $val }}" {{ old('style') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label>Темы (через запятую)</label>
    <input type="text" name="knowledge" value="{{ old('knowledge') }}" placeholder="dota, gaming, мемы, cs2">
    <div style="font-size:10px;color:var(--text-muted);margin-top:4px;">Бот будет реагировать на эти темы в первую очередь</div>
</div>

<div class="grid-2">
    <div class="form-group">
        <label>Токсичность — <span id="{{ $edit ? 'edit-toxicity' : 'create-toxicity' }}">{{ round(old('toxicity', 0.2) * 100) }}%</span></label>
        <input type="range" name="toxicity" min="0" max="1" step="0.05"
            value="{{ old('toxicity', 0.2) }}"
            data-display="{{ $edit ? 'edit-toxicity' : 'create-toxicity' }}">
    </div>
    <div class="form-group">
        <label>Активность — <span id="{{ $edit ? 'edit-verbosity' : 'create-verbosity' }}">{{ round(old('verbosity', 0.5) * 100) }}%</span></label>
        <input type="range" name="verbosity" min="0" max="1" step="0.05"
            value="{{ old('verbosity', 0.5) }}"
            data-display="{{ $edit ? 'edit-verbosity' : 'create-verbosity' }}">
    </div>
</div>

<div class="form-group">
    <label>Вес (1-100) — чем больше, тем чаще отвечает</label>
    <input type="number" name="weight" value="{{ old('weight', 10) }}" min="1" max="100">
</div>
