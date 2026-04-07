@php $edit = $edit ?? false; @endphp

<div class="grid-2">
    <div class="form-group">
        <label>Системное имя (slug)</label>
        <input type="text" name="name" value="{{ old('name') }}" placeholder="sarcastic" required maxlength="32"
            {{ $edit ? 'readonly style=opacity:0.5' : '' }}>
        <div style="font-size:10px;color:var(--text-muted);margin-top:3px;">Только латиница, без пробелов</div>
    </div>
    <div class="form-group">
        <label>Название с эмодзи</label>
        <input type="text" name="label" value="{{ old('label') }}" placeholder="😏 Саркастик" required maxlength="64">
    </div>
</div>

<div class="form-group">
    <label>Системный промпт — характер</label>
    <textarea name="system_prompt" rows="4" required
        placeholder="Ты саркастичный скептик. Сомневаешься во всём...">{{ old('system_prompt') }}</textarea>
    <div style="font-size:10px;color:var(--text-muted);margin-top:3px;">Основной характер бота — кто он и как себя ведёт</div>
</div>

<div class="form-group">
    <label>Поведенческий промпт</label>
    <textarea name="behavior_prompt" rows="3"
        placeholder="Иногда реагируй не на основную мысль...">{{ old('behavior_prompt') }}</textarea>
    <div style="font-size:10px;color:var(--text-muted);margin-top:3px;">Правила поведения — как часто, на что реагировать</div>
</div>

<div class="form-group">
    <label>Инструкция по смайлам</label>
    <textarea name="emoji_instruction" rows="3"
        placeholder="Используй: KEKW, 💀, лол...">{{ old('emoji_instruction') }}</textarea>
</div>

<div class="grid-2">
    <div class="form-group">
        <label>Twitch эмоуты (через запятую)</label>
        <input type="text" name="emotes" value="{{ old('emotes') }}" placeholder="KEKW, Pog, monkaS">
    </div>
    <div class="form-group">
        <label>Эмодзи (через запятую)</label>
        <input type="text" name="emoji" value="{{ old('emoji') }}" placeholder="💀, 😭, 🔥">
    </div>
</div>

<div class="form-group">
    <label>Русские слова/сленг (через запятую)</label>
    <input type="text" name="ru_words" value="{{ old('ru_words') }}" placeholder="лол, кек, ору, мда">
</div>

<div class="form-group" style="display:flex;align-items:center;gap:10px;">
    <input type="checkbox" name="is_active" value="1" id="is_active"
        {{ old('is_active', true) ? 'checked' : '' }}
        style="width:16px;height:16px;padding:0;accent-color:var(--accent);">
    <label for="is_active" style="margin:0;text-transform:none;letter-spacing:0;font-size:12px;color:var(--text);">
        Активен (боты с этим типом будут отвечать)
    </label>
</div>
