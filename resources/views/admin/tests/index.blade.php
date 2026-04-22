@extends('admin.layouts.app')
@section('title', 'Тесты')
@section('content')
<div class="space-y-6">

    {{-- Заголовок --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-white">🧪 Тесты системы</h1>
    </div>

    {{-- Вкладки --}}
    <div class="flex gap-2 border-b border-gray-700">
        <button onclick="showTab('whisper')" id="tab-whisper"
            class="tab-btn px-4 py-2 text-sm font-medium text-purple-400 border-b-2 border-purple-400">
            🎙️ Whisper
        </button>
        <button onclick="showTab('chat')" id="tab-chat"
            class="tab-btn px-4 py-2 text-sm font-medium text-gray-400 border-b-2 border-transparent hover:text-white">
            💬 Чат боты
        </button>
        <button onclick="showTab('viewers')" id="tab-viewers"
            class="tab-btn px-4 py-2 text-sm font-medium text-gray-400 border-b-2 border-transparent hover:text-white">
            👁️ Зрители
        </button>
        <button onclick="showTab('botchat')" id="tab-botchat"
            class="tab-btn px-4 py-2 text-sm font-medium text-gray-400 border-b-2 border-transparent hover:text-white">
            🤖 Общение ботов
        </button>
    </div>

    {{-- ===== TAB: WHISPER ===== --}}
    <div id="tab-content-whisper" class="tab-content">
        <div class="bg-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">🎙️ Тест распознавания речи стримера</h2>
            <p class="text-gray-400 text-sm">Запускает Whisper на канале и распознаёт что говорит стример в реальном времени.</p>

            <div class="flex gap-3">
                <input id="whisper-channel" type="text" placeholder="Канал (напр. gars_sem)"
                    class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-purple-500 focus:outline-none">
                <button onclick="startWhisper()"
                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium transition">
                    ▶ Запустить
                </button>
                <button onclick="stopWhisper()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    ⏹ Стоп
                </button>
            </div>

            <div id="whisper-status" class="flex items-center gap-2 text-sm text-gray-400">
                <span id="whisper-dot" class="w-2 h-2 rounded-full bg-gray-500"></span>
                <span id="whisper-status-text">Не запущен</span>
            </div>

            <div>
                <div class="text-sm font-medium text-gray-300 mb-2">🗣️ Распознанная речь:</div>
                <div id="whisper-speeches" class="space-y-2 max-h-48 overflow-y-auto">
                    <div class="text-gray-500 text-sm italic">Пока пусто...</div>
                </div>
            </div>

            <div>
                <div class="text-sm font-medium text-gray-300 mb-2">📋 Логи:</div>
                <div id="whisper-log"
                    class="bg-gray-900 rounded-lg p-3 font-mono text-xs text-green-400 max-h-64 overflow-y-auto whitespace-pre-wrap">
                    Логи появятся здесь...
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TAB: CHAT ===== --}}
    <div id="tab-content-chat" class="tab-content hidden">
        <div class="bg-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">💬 Тест чат-ботов</h2>
            <p class="text-gray-400 text-sm">Проверяет доступ каждого бота к каналу. Подписывает только тех кто ещё не подписан.</p>

            <div class="flex gap-3">
                <input id="chat-channel" type="text" placeholder="Канал (напр. kunay0)"
                    class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-blue-500 focus:outline-none">
                <button onclick="testChat()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    🔍 Проверить доступ
                </button>
                <input id="follow-limit" type="number" min="1" max="1000" placeholder="Кол-во (все)"
                    class="w-32 bg-gray-700 text-white rounded-lg px-3 py-2 text-sm border border-gray-600 focus:border-green-500 focus:outline-none"
                    title="Сколько ботов подписать. Оставь пустым — подпишет всех кто не подписан">
                <button onclick="followBots()" id="follow-btn"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                    ➕ Подписать ботов
                </button>
            </div>

            {{-- Статистика подписок --}}
            <div id="follow-stats" class="hidden flex items-center gap-6 bg-gray-700 rounded-lg px-4 py-3">
                <div class="text-sm text-gray-300">
                    Подписаны на канал:
                    <span id="follow-count" class="font-bold text-green-400 ml-1">0</span>
                    <span class="text-gray-500 mx-1">/</span>
                    <span id="follow-total" class="font-bold text-white">0</span>
                    ботов
                </div>
                <div id="follow-all-done" class="hidden text-sm text-green-400 font-medium">
                    ✅ Все боты уже подписаны
                </div>
                <div id="follow-need" class="hidden text-sm text-yellow-400">
                    ⚠️ Нужно подписать: <span id="follow-need-count" class="font-bold">0</span>
                </div>
            </div>

            {{-- Прогресс подписки --}}
            <div id="chat-progress" class="hidden space-y-2">
                <div class="flex items-center justify-between text-sm text-gray-400">
                    <span>Подписываем ботов...</span>
                    <span id="progress-text">0 / 0</span>
                </div>
                <div class="w-full bg-gray-700 rounded-full h-2">
                    <div id="chat-progress-bar" class="bg-green-500 h-2 rounded-full transition-all" style="width:0%"></div>
                </div>
            </div>

            {{-- Результаты --}}
            <div id="chat-results" class="space-y-2"></div>
        </div>
    </div>

    {{-- ===== TAB: VIEWERS ===== --}}
    <div id="tab-content-viewers" class="tab-content hidden">
        <div class="bg-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">👁️ Накрутка зрителей</h2>
            <p class="text-gray-400 text-sm">Открывает N анонимных IRC подключений к каналу. Каждое подключение = +1 зритель.</p>

            <div class="flex gap-3">
                <input id="viewers-channel" type="text" placeholder="Канал (напр. kunay0)"
                    class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-yellow-500 focus:outline-none">
                <input id="viewers-count" type="number" placeholder="Кол-во" min="1" max="500" value="50"
                    class="w-28 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-yellow-500 focus:outline-none"
                    title="Сколько зрителей набрать">
                <input id="viewers-rate" type="number" placeholder="в мин" min="1" max="60" value="7"
                    class="w-24 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-yellow-500 focus:outline-none"
                    title="Сколько зрителей добавлять в минуту">
                <button onclick="startViewers()" id="viewers-start-btn"
                    class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium transition">
                    ▶ Запустить
                </button>
                <button onclick="stopViewers()" id="viewers-stop-btn" disabled
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition opacity-50 cursor-not-allowed">
                    ⏹ Стоп
                </button>
            </div>

            {{-- Статус --}}
            <div class="flex items-center gap-4 bg-gray-700 rounded-lg px-4 py-3">
                <div class="flex items-center gap-2">
                    <span id="viewers-dot" class="w-3 h-3 rounded-full bg-gray-500"></span>
                    <span id="viewers-status-text" class="text-sm text-gray-400">Не запущен</span>
                </div>
                <div class="text-sm text-gray-300">
                    Активных: <span id="viewers-active" class="font-bold text-yellow-400">0</span>
                    <span class="text-gray-600 mx-1">/</span>
                    <span id="viewers-total" class="font-bold text-white">0</span>
                </div>
            </div>

            {{-- Мини-график --}}
            <div class="bg-gray-900 rounded-xl p-4">
                <div class="text-sm text-gray-400 mb-3">📈 Активных зрителей в реальном времени</div>
                <div id="viewers-graph" class="flex items-end gap-1 h-16">
                    {{-- бары добавляются JS --}}
                </div>
            </div>

            {{-- Лог --}}
            <div id="viewers-log" class="bg-gray-900 rounded-lg p-3 font-mono text-xs text-yellow-400 max-h-48 overflow-y-auto">
                <div class="text-gray-500">Лог появится после запуска...</div>
            </div>
        </div>
    </div>

</div>

    {{-- ===== TAB: BOT CHAT ===== --}}
    <div id="tab-content-botchat" class="tab-content hidden">
        <div class="bg-gray-800 rounded-xl p-6 space-y-4">
            <h2 class="text-lg font-semibold text-white">🤖 Тест общения ботов</h2>
            <p class="text-gray-400 text-sm">Боты читают чат и отвечают через Ollama. Лог обновляется в реальном времени.</p>

            {{-- Настройки --}}
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Канал</label>
                    <input id="bc-channel" type="text" placeholder="напр. gars_sem"
                        class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Режим</label>
                    <select id="bc-mode" class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none">
                        <option value="real">🌐 Реальный чат канала</option>
                        <option value="self">🔄 Боты между собой</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Кол-во ботов</label>
                    <input id="bc-bots" type="number" min="1" max="20" value="3"
                        class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Длительность (мин)</label>
                    <input id="bc-duration" type="number" min="1" max="60" value="5"
                        class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="text-xs text-gray-400 mb-1 block">Задержка между сообщениями (сек)</label>
                    <input id="bc-delay" type="number" min="5" max="300" value="15"
                        class="w-full bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none">
                </div>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-300">
                    <input type="checkbox" id="bc-dryrun" class="w-4 h-4 rounded">
                    <span>🔍 Dry-run — только показывать, не отправлять в Twitch</span>
                </label>
            </div>

            {{-- Кнопки --}}
            <div class="flex gap-3">
                <button onclick="botChatStart()" id="bc-start-btn"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                    ▶ Запустить
                </button>
                <button onclick="botChatStop()" id="bc-stop-btn" disabled
                    class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition opacity-50 cursor-not-allowed">
                    ⏹ Остановить
                </button>
                <div id="bc-status" class="flex items-center gap-2 text-sm text-gray-400 ml-2">
                    <span id="bc-dot" class="w-2 h-2 rounded-full bg-gray-500"></span>
                    <span id="bc-status-text">Не запущен</span>
                </div>
            </div>

            {{-- Лог --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-medium text-gray-300">📋 Лог в реальном времени</div>
                    <button onclick="document.getElementById('bc-log').innerHTML=''" class="text-xs text-gray-500 hover:text-gray-300">Очистить</button>
                </div>
                <div id="bc-log"
                    class="bg-gray-900 rounded-lg p-3 font-mono text-xs text-green-400 max-h-64 overflow-y-auto space-y-1">
                    <div class="text-gray-500">Лог появится после запуска...</div>
                </div>
            </div>

            {{-- Живой чат + ручная отправка --}}
            <div class="grid grid-cols-2 gap-4">

                {{-- Живой чат --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <div class="text-sm font-medium text-gray-300">👁️ Живой чат канала</div>
                        <span class="text-xs text-gray-500" id="live-chat-status">не активен</span>
                    </div>
                    <div id="live-chat-messages"
                        class="bg-gray-900 rounded-lg p-3 font-mono text-xs max-h-64 overflow-y-auto space-y-1">
                        <div class="text-gray-500">Запусти тест чтобы видеть чат...</div>
                    </div>
                </div>

                {{-- Ручная отправка --}}
                <div>
                    <div class="text-sm font-medium text-gray-300 mb-2">✍️ Отправить вручную</div>
                    <div class="space-y-2">
                        <select id="send-account" class="w-full bg-gray-700 text-white rounded-lg px-3 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none">
                            <option value="">Выбери аккаунт...</option>
                            @foreach(\App\Models\Account::where('type','chatbot')->where('is_active',true)->get() as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->username }}</option>
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <input id="send-message" type="text" placeholder="Текст сообщения..."
                                class="flex-1 bg-gray-700 text-white rounded-lg px-3 py-2 text-sm border border-gray-600 focus:border-indigo-500 focus:outline-none"
                                onkeydown="if(event.key==='Enter') sendManual()">
                            <button onclick="sendManual()"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition">
                                Отправить
                            </button>
                        </div>
                        <div id="send-result" class="text-xs text-gray-500"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

// ─── Tabs ───
function showTab(name) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(el => {
        el.classList.remove('text-purple-400','text-blue-400','text-yellow-400','border-purple-400','border-blue-400','border-yellow-400');
        el.classList.add('text-gray-400','border-transparent');
    });
    document.getElementById('tab-content-' + name).classList.remove('hidden');
    const colors = {whisper:'purple', chat:'blue', viewers:'yellow', botchat:'indigo'};
    const btn = document.getElementById('tab-' + name);
    btn.classList.remove('text-gray-400','border-transparent');
    btn.classList.add(`text-${colors[name]}-400`, `border-${colors[name]}-400`);
}

// ─── Whisper ───
let whisperTimer = null;
const whisperChannel = () => document.getElementById('whisper-channel').value.trim();

async function startWhisper() {
    if (!whisperChannel()) return alert('Укажи канал');
    await fetch('{{ route("admin.tests.whisper") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
        body: JSON.stringify({channel: whisperChannel(), action:'start'})
    });
    setWhisperStatus(true);
    if (whisperTimer) clearInterval(whisperTimer);
    whisperTimer = setInterval(pollWhisper, 3000);
}

async function stopWhisper() {
    if (!whisperChannel()) return;
    await fetch('{{ route("admin.tests.whisper") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
        body: JSON.stringify({channel: whisperChannel(), action:'stop'})
    });
    setWhisperStatus(false);
    if (whisperTimer) clearInterval(whisperTimer);
}

function setWhisperStatus(running) {
    const dot  = document.getElementById('whisper-dot');
    const text = document.getElementById('whisper-status-text');
    dot.className  = 'w-2 h-2 rounded-full ' + (running ? 'bg-green-500 animate-pulse' : 'bg-gray-500');
    text.textContent = running ? 'Запущен — слушаем...' : 'Остановлен';
}

async function pollWhisper() {
    const ch = whisperChannel();
    if (!ch) return;
    const r = await fetch(`{{ route("admin.tests.whisper.log") }}?channel=${ch}`);
    const d = await r.json();
    document.getElementById('whisper-log').textContent = d.lines.join('\n');
    if (d.speeches.length) {
        document.getElementById('whisper-speeches').innerHTML = d.speeches.map(s =>
            `<div class="bg-gray-700 rounded-lg px-3 py-2 text-sm text-white">
                <span class="text-gray-400 text-xs">${new Date(s.timestamp*1000).toLocaleTimeString()}</span>
                <span class="ml-2">${s.text}</span>
            </div>`
        ).join('');
    }
    setWhisperStatus(d.running);
}

// ─── Chat ───
let lastCheckResults = [];

async function testChat() {
    const ch = document.getElementById('chat-channel').value.trim();
    if (!ch) return alert('Укажи канал');

    document.getElementById('chat-results').innerHTML =
        '<div class="text-gray-400 text-sm animate-pulse">Проверяем доступ ботов...</div>';
    document.getElementById('follow-stats').classList.add('hidden');

    const r = await fetch('{{ route("admin.tests.chat") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
        body: JSON.stringify({channel: ch})
    });
    const d = await r.json();
    lastCheckResults = d.results || [];

    renderChatResults(lastCheckResults);
    updateFollowStats(ch, lastCheckResults);
}

function updateFollowStats(channel, results) {
    const total      = results.filter(r => r.account).length;
    const needFollow = results.filter(r => r.status === 'needs_follow').length;

    // Запрашиваем сколько уже подписаны
    fetch(`{{ route("admin.tests.follow.status") }}?channel=${channel}`)
        .then(r => r.json())
        .then(d => {
            const followed = (d.progress || []).filter(p => p.status === 'followed' || p.status === 'already_following').length;

            document.getElementById('follow-stats').classList.remove('hidden');
            document.getElementById('follow-count').textContent  = followed;
            document.getElementById('follow-total').textContent  = total;

            if (needFollow === 0) {
                document.getElementById('follow-all-done').classList.remove('hidden');
                document.getElementById('follow-need').classList.add('hidden');
                // Блокируем кнопку если подписывать некого
                document.getElementById('follow-btn').disabled = true;
                document.getElementById('follow-btn').classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                document.getElementById('follow-all-done').classList.add('hidden');
                document.getElementById('follow-need').classList.remove('hidden');
                document.getElementById('follow-need-count').textContent = needFollow;
                document.getElementById('follow-btn').disabled = false;
                document.getElementById('follow-btn').classList.remove('opacity-50', 'cursor-not-allowed');
            }
        });
}

function renderChatResults(results) {
    const icons  = {ok:'✅', needs_phone:'📵', needs_follow:'👤', unknown:'❓', no_account:'⚠️', connection_failed:'❌', invalid_token:'🔑', viewer_account:'👁️'};
    const labels = {ok:'Может писать', needs_phone:'Нужен телефон', needs_follow:'Нужна подписка', unknown:'Неизвестно', no_account:'Нет аккаунта', connection_failed:'Ошибка', invalid_token:'Токен невалиден', viewer_account:'Зритель (не чатбот)'};
    const colors = {ok:'border-green-500', needs_phone:'border-red-500', needs_follow:'border-yellow-500', unknown:'border-gray-500', no_account:'border-gray-600', connection_failed:'border-red-600', invalid_token:'border-orange-500', viewer_account:'border-gray-500'};

    document.getElementById('chat-results').innerHTML = results.map(r =>
        `<div class="flex items-center justify-between bg-gray-700 rounded-lg px-4 py-3 border-l-4 ${colors[r.status]||'border-gray-500'}">
            <div>
                <span class="font-medium text-white">${r.bot}</span>
                <span class="text-gray-400 text-sm ml-2">${r.account || '—'}</span>
                <span class="text-gray-500 text-xs ml-2">[${r.style||''}]</span>
            </div>
            <span class="text-sm">${icons[r.status]||'❓'} ${labels[r.status]||r.status}</span>
        </div>`
    ).join('');
}

let followPollTimer = null;

async function followBots() {
    const ch = document.getElementById('chat-channel').value.trim();
    if (!ch) return alert('Укажи канал');

    const needFollow = lastCheckResults.filter(r => r.status === 'needs_follow');
    if (needFollow.length === 0) {
        return alert('Все боты уже могут писать или не требуют подписки');
    }

    const limit = document.getElementById('follow-limit').value;
    const limitNum = limit ? parseInt(limit) : 0;
    const total = limitNum > 0 ? Math.min(limitNum, needFollow.length) : needFollow.length;

    document.getElementById('chat-progress').classList.remove('hidden');
    document.getElementById('follow-btn').disabled = true;
    document.getElementById('follow-btn').classList.add('opacity-50', 'cursor-not-allowed');
    document.getElementById('progress-text').textContent = `0 / ${total}`;
    document.getElementById('chat-progress-bar').style.width = '0%';
    document.getElementById('chat-results').innerHTML =
        `<div class="text-gray-400 text-sm animate-pulse">⏳ Подписываем ${total} ботов (20-40 сек между каждым)...</div>
         <div id="follow-live-log" class="mt-2 space-y-1"></div>`;

    // Запускаем поллинг прогресса каждые 4 сек
    if (followPollTimer) clearInterval(followPollTimer);
    followPollTimer = setInterval(() => pollFollowProgress(ch, total), 4000);

    // Запускаем подписку (долгий запрос)
    const r = await fetch('{{ route("admin.tests.follow") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({channel: ch, limit: limitNum})
    });
    const d = await r.json();

    // Останавливаем поллинг
    clearInterval(followPollTimer);
    followPollTimer = null;

    document.getElementById('chat-progress').classList.add('hidden');
    document.getElementById('chat-progress-bar').style.width = '100%';

    const followed        = (d.results||[]).filter(r => r.status === 'followed').length;
    const alreadyFollowed = (d.results||[]).filter(r => r.status === 'already_following').length;
    const failed          = (d.results||[]).filter(r => r.status === 'failed').length;

    document.getElementById('follow-count').textContent = followed + alreadyFollowed;
    document.getElementById('follow-all-done').classList.remove('hidden');
    document.getElementById('follow-need').classList.add('hidden');
    document.getElementById('follow-btn').disabled = false;
    document.getElementById('follow-btn').classList.remove('opacity-50', 'cursor-not-allowed');

    renderFollowResults(d.results || []);
}

async function pollFollowProgress(channel, total) {
    try {
        const r = await fetch(`{{ route("admin.tests.follow.status") }}?channel=${channel}`);
        const d = await r.json();
        const progress = d.progress || [];
        if (!progress.length) return;

        const done = progress.filter(p => p.status === 'followed' || p.status === 'failed').length;
        const pct  = total > 0 ? Math.round((done / total) * 100) : 0;

        document.getElementById('progress-text').textContent = `${done} / ${total}`;
        document.getElementById('chat-progress-bar').style.width = `${pct}%`;

        // Живой лог
        const icons  = {followed: '✅', already_following: '⏩', failed: '❌'};
        const labels = {followed: 'Подписан', already_following: 'Уже был', failed: 'Ошибка'};
        const logEl  = document.getElementById('follow-live-log');
        if (logEl) {
            logEl.innerHTML = progress.map(p =>
                `<div class="flex items-center justify-between bg-gray-700 rounded px-3 py-1 text-sm">
                    <span class="text-white">${p.bot} <span class="text-gray-400 text-xs">${p.account||''}</span></span>
                    <span ${p.error ? `title="${p.error}" class="cursor-help"` : ''}>
                        ${icons[p.status]||'⏳'} ${labels[p.status]||p.status}
                        ${p.error ? '<span class="text-gray-500 text-xs ml-1">ℹ️</span>' : ''}
                    </span>
                </div>`
            ).join('');
        }
    } catch(e) {}
}

function renderFollowResults(results) {
    const icons  = {followed: '✅', already_following: '⏩', failed: '❌'};
    const labels = {followed: 'Подписан', already_following: 'Уже подписан', failed: 'Ошибка'};

    const followed        = results.filter(r => r.status === 'followed').length;
    const alreadyFollowed = results.filter(r => r.status === 'already_following').length;
    const failed          = results.filter(r => r.status === 'failed').length;

    document.getElementById('chat-results').innerHTML = [
        `<div class="bg-gray-700 rounded-lg px-4 py-3 text-sm text-gray-300">
            ✅ Подписано: <span class="text-green-400 font-bold">${followed}</span>
            &nbsp;⏩ Уже были: <span class="text-blue-400 font-bold">${alreadyFollowed}</span>
            &nbsp;❌ Ошибок: <span class="text-red-400 font-bold">${failed}</span>
        </div>`,
        ...results.map(r =>
            `<div class="flex items-center justify-between bg-gray-700 rounded-lg px-4 py-2">
                <span class="text-white text-sm">${r.bot} <span class="text-gray-400">${r.account||''}</span></span>
                <span class="text-sm" ${r.error ? `title="${r.error}" class="cursor-help"` : ''}>
                    ${icons[r.status]||'❓'} ${labels[r.status]||r.status}
                    ${r.error ? `<span class="text-gray-500 text-xs ml-1" title="${r.error}">ℹ️</span>` : ''}
                </span>
            </div>`
        )
    ].join('');
}

// ─── Bot Chat ───
let bcPollTimer = null;

async function botChatStart() {
    const channel  = document.getElementById('bc-channel').value.trim();
    const botCount = document.getElementById('bc-bots').value;
    const duration = document.getElementById('bc-duration').value;
    const delay    = document.getElementById('bc-delay').value;
    const mode     = document.getElementById('bc-mode').value;

    if (!channel) return alert('Укажи канал');

    document.getElementById('bc-start-btn').disabled = true;
    document.getElementById('bc-start-btn').classList.add('opacity-50', 'cursor-not-allowed');
    document.getElementById('bc-stop-btn').disabled = false;
    document.getElementById('bc-stop-btn').classList.remove('opacity-50', 'cursor-not-allowed');
    setBcStatus(true);
    document.getElementById('bc-log').innerHTML = '';

    await fetch('{{ route("admin.tests.bot-chat.start") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({channel, bot_count: botCount, duration, delay, mode})
    });

    // Поллинг лога каждые 3 сек
    if (bcPollTimer) clearInterval(bcPollTimer);
    bcPollTimer = setInterval(() => pollBotChatLog(channel), 3000);
}

async function botChatStop() {
    const channel = document.getElementById('bc-channel').value.trim();
    if (!channel) return;

    await fetch('{{ route("admin.tests.bot-chat.stop") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({channel})
    });

    clearInterval(bcPollTimer);
    bcPollTimer = null;
    stopLiveChat();
    setBcStatus(false);
    document.getElementById('bc-start-btn').disabled = false;
    document.getElementById('bc-start-btn').classList.remove('opacity-50', 'cursor-not-allowed');
    document.getElementById('bc-stop-btn').disabled = true;
    document.getElementById('bc-stop-btn').classList.add('opacity-50', 'cursor-not-allowed');
}

async function pollBotChatLog(channel) {
    try {
        const r = await fetch(`{{ route("admin.tests.bot-chat.log") }}?channel=${channel}`);
        const d = await r.json();

        const logEl = document.getElementById('bc-log');
        if (d.log && d.log.length) {
            logEl.innerHTML = d.log.map(entry => {
                const color = entry.text.startsWith('✅') ? 'text-green-400'
                            : entry.text.startsWith('❌') ? 'text-red-400'
                            : entry.text.startsWith('⚠️') ? 'text-yellow-400'
                            : entry.text.startsWith('🏁') ? 'text-blue-400'
                            : 'text-green-400';
                return `<div class="${color}"><span class="text-gray-500">${entry.time}</span> ${entry.text}</div>`;
            }).join('');
            logEl.scrollTop = logEl.scrollHeight;
        }

        // Если завершён — останавливаем поллинг
        if (d.status === 'finished' || d.status === 'stopped') {
            clearInterval(bcPollTimer);
            bcPollTimer = null;
            setBcStatus(false);
            document.getElementById('bc-start-btn').disabled = false;
            document.getElementById('bc-start-btn').classList.remove('opacity-50', 'cursor-not-allowed');
            document.getElementById('bc-stop-btn').disabled = true;
            document.getElementById('bc-stop-btn').classList.add('opacity-50', 'cursor-not-allowed');
        }
    } catch(e) {}
}

// Живой чат
let liveChatTimer = null;

async function startLiveChat(channel) {
    if (liveChatTimer) clearInterval(liveChatTimer);
    document.getElementById('live-chat-status').textContent = '🟢 активен';
    liveChatTimer = setInterval(() => pollLiveChat(channel), 5000);
    pollLiveChat(channel);
}

function stopLiveChat() {
    if (liveChatTimer) clearInterval(liveChatTimer);
    liveChatTimer = null;
    document.getElementById('live-chat-status').textContent = 'не активен';
}

async function pollLiveChat(channel) {
    try {
        const r = await fetch(`{{ route("admin.tests.chat.live") }}?channel=${channel}`);
        const d = await r.json();
        if (!d.messages || !d.messages.length) return;

        const el = document.getElementById('live-chat-messages');
        const colors = ['text-blue-400', 'text-green-400', 'text-yellow-400', 'text-pink-400', 'text-purple-400'];

        d.messages.forEach(msg => {
            const color = colors[msg.author.length % colors.length];
            const div = document.createElement('div');
            div.innerHTML = `<span class="${color} font-bold">${msg.author}</span>: <span class="text-white">${msg.message}</span>`;
            el.appendChild(div);
        });

        el.scrollTop = el.scrollHeight;

        // Чистим если слишком много
        while (el.children.length > 100) el.removeChild(el.firstChild);
    } catch(e) {}
}

// Ручная отправка
async function sendManual() {
    const channel   = document.getElementById('bc-channel').value.trim();
    const accountId = document.getElementById('send-account').value;
    const message   = document.getElementById('send-message').value.trim();
    const resultEl  = document.getElementById('send-result');

    if (!channel) return resultEl.textContent = '❌ Укажи канал выше';
    if (!accountId) return resultEl.textContent = '❌ Выбери аккаунт';
    if (!message) return resultEl.textContent = '❌ Введи сообщение';

    resultEl.textContent = '⏳ Отправляем...';

    try {
        const r = await fetch('{{ route("admin.tests.chat.send") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
            body: JSON.stringify({channel, account_id: accountId, message})
        });
        const d = await r.json();
        resultEl.textContent = d.ok ? `✅ Отправлено от ${d.account}` : `❌ Ошибка`;
        if (d.ok) document.getElementById('send-message').value = '';
    } catch(e) {
        resultEl.textContent = '❌ Ошибка запроса';
    }
}

function setBcStatus(running) {
    const dot  = document.getElementById('bc-dot');
    const text = document.getElementById('bc-status-text');
    dot.className  = 'w-2 h-2 rounded-full ' + (running ? 'bg-green-500 animate-pulse' : 'bg-gray-500');
    text.textContent = running ? 'Работает...' : 'Остановлен';
}

// ─── Viewers ───
let viewersTimer = null;
let viewersHistory = [];

async function startViewers() {
    const ch    = document.getElementById('viewers-channel').value.trim();
    const count = document.getElementById('viewers-count').value;
    if (!ch) return alert('Укажи канал');

    document.getElementById('viewers-start-btn').disabled = true;
    document.getElementById('viewers-start-btn').classList.add('opacity-50', 'cursor-not-allowed');
    document.getElementById('viewers-stop-btn').disabled = false;
    document.getElementById('viewers-stop-btn').classList.remove('opacity-50', 'cursor-not-allowed');

    setViewersStatus(true, `Запускаем ${count} зрителей...`);
    addViewersLog(`🚀 Запуск: канал #${ch}, зрителей: ${count}`);

    const rate = document.getElementById('viewers-rate').value;
    await fetch('{{ route("admin.tests.viewers.start") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({channel: ch, count: parseInt(count), rate: parseInt(rate)})
    });

    // Поллинг статистики каждые 5 сек
    if (viewersTimer) clearInterval(viewersTimer);
    viewersTimer = setInterval(() => pollViewers(ch), 5000);
    setTimeout(() => pollViewers(ch), 2000);
}

async function stopViewers() {
    const ch = document.getElementById('viewers-channel').value.trim();
    if (!ch) return;

    await fetch('{{ route("admin.tests.viewers.stop") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf},
        body: JSON.stringify({channel: ch})
    });

    if (viewersTimer) clearInterval(viewersTimer);
    viewersTimer = null;
    setViewersStatus(false, 'Остановлен');
    addViewersLog('⏹ Остановлено');

    document.getElementById('viewers-start-btn').disabled = false;
    document.getElementById('viewers-start-btn').classList.remove('opacity-50', 'cursor-not-allowed');
    document.getElementById('viewers-stop-btn').disabled = true;
    document.getElementById('viewers-stop-btn').classList.add('opacity-50', 'cursor-not-allowed');
    document.getElementById('viewers-active').textContent = '0';
}

async function pollViewers(channel) {
    try {
        const r = await fetch(`{{ route("admin.tests.viewers.stats") }}?channel=${channel}`);
        const d = await r.json();

        document.getElementById('viewers-active').textContent = d.active || 0;
        document.getElementById('viewers-total').textContent  = d.total  || 0;

        setViewersStatus(d.running, d.running ? `Работает — ${d.active}/${d.total} активных` : 'Остановлен');

        // Обновляем мини-график
        viewersHistory.push(d.active || 0);
        if (viewersHistory.length > 20) viewersHistory.shift();
        updateViewersGraph();

        if (!d.running && viewersTimer) {
            clearInterval(viewersTimer);
            viewersTimer = null;
        }
    } catch(e) {}
}

function updateViewersGraph() {
    const graph = document.getElementById('viewers-graph');
    const max   = Math.max(...viewersHistory, 1);
    graph.innerHTML = viewersHistory.map(v => {
        const h = Math.max(4, Math.round((v / max) * 60));
        return `<div class="flex-1 bg-yellow-500 rounded-t opacity-80" style="height:${h}px" title="${v}"></div>`;
    }).join('');
}

function setViewersStatus(running, text) {
    const dot  = document.getElementById('viewers-dot');
    const txt  = document.getElementById('viewers-status-text');
    dot.className = 'w-3 h-3 rounded-full ' + (running ? 'bg-green-500 animate-pulse' : 'bg-gray-500');
    txt.textContent = text || (running ? 'Работает' : 'Остановлен');
    txt.className   = 'text-sm ' + (running ? 'text-green-400' : 'text-gray-400');
}

function addViewersLog(msg) {
    const log = document.getElementById('viewers-log');
    const time = new Date().toLocaleTimeString();
    const div  = document.createElement('div');
    div.textContent = `${time} ${msg}`;
    if (log.firstChild && log.firstChild.classList && log.firstChild.textContent.includes('Лог появится')) {
        log.innerHTML = '';
    }
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
}
</script>
@endpush
@endsection