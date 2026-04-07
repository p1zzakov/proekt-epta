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

            {{-- Статус --}}
            <div id="whisper-status" class="flex items-center gap-2 text-sm text-gray-400">
                <span id="whisper-dot" class="w-2 h-2 rounded-full bg-gray-500"></span>
                <span id="whisper-status-text">Не запущен</span>
            </div>

            {{-- Распознанные фразы --}}
            <div>
                <div class="text-sm font-medium text-gray-300 mb-2">🗣️ Распознанная речь:</div>
                <div id="whisper-speeches" class="space-y-2 max-h-48 overflow-y-auto">
                    <div class="text-gray-500 text-sm italic">Пока пусто...</div>
                </div>
            </div>

            {{-- Логи --}}
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
            <p class="text-gray-400 text-sm">Проверяет доступ каждого бота к каналу. При необходимости подписывает ботов.</p>

            <div class="flex gap-3">
                <input id="chat-channel" type="text" placeholder="Канал (напр. kunay0)"
                    class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-blue-500 focus:outline-none">
                <button onclick="testChat()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    🔍 Проверить доступ
                </button>
                <button onclick="followBots()"
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
                    ➕ Подписать ботов
                </button>
            </div>

            {{-- Прогресс --}}
            <div id="chat-progress" class="hidden">
                <div class="text-sm text-gray-400 mb-1">Прогресс подписки...</div>
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
            <h2 class="text-lg font-semibold text-white">👁️ Тест накрутки зрителей</h2>
            <p class="text-gray-400 text-sm">Запускает зрителей на канал и показывает статистику в реальном времени.</p>

            <div class="flex gap-3">
                <input id="viewers-channel" type="text" placeholder="Канал"
                    class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-yellow-500 focus:outline-none">
                <input id="viewers-count" type="number" placeholder="Кол-во" min="1" max="1000" value="50"
                    class="w-28 bg-gray-700 text-white rounded-lg px-4 py-2 text-sm border border-gray-600 focus:border-yellow-500 focus:outline-none">
                <button onclick="startViewers()"
                    class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg text-sm font-medium transition">
                    ▶ Запустить
                </button>
                <button onclick="stopViewers()"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">
                    ⏹ Стоп
                </button>
            </div>

            {{-- График --}}
            <div class="bg-gray-900 rounded-xl p-4">
                <div class="text-sm text-gray-400 mb-3">📈 Зрители в реальном времени</div>
                <canvas id="viewers-chart" height="120"></canvas>
            </div>

            {{-- Статс --}}
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-400" id="stat-active">0</div>
                    <div class="text-xs text-gray-400 mt-1">Активных</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-400" id="stat-total">0</div>
                    <div class="text-xs text-gray-400 mt-1">Всего запущено</div>
                </div>
                <div class="bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-400" id="stat-failed">0</div>
                    <div class="text-xs text-gray-400 mt-1">Ошибок</div>
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
    const colors = {whisper:'purple', chat:'blue', viewers:'yellow'};
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

    // Логи
    document.getElementById('whisper-log').textContent = d.lines.join('\n');

    // Фразы
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
async function testChat() {
    const ch = document.getElementById('chat-channel').value.trim();
    if (!ch) return alert('Укажи канал');
    document.getElementById('chat-results').innerHTML = '<div class="text-gray-400 text-sm animate-pulse">Проверяем доступ ботов...</div>';
    const r = await fetch('{{ route("admin.tests.chat") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
        body: JSON.stringify({channel: ch})
    });
    const d = await r.json();
    renderChatResults(d.results);
}

function renderChatResults(results) {
    const icons = {ok:'✅', needs_phone:'📵', needs_follow:'👤', unknown:'❓', no_account:'⚠️', connection_failed:'❌'};
    const labels = {ok:'Может писать', needs_phone:'Нужен телефон', needs_follow:'Нужна подписка', unknown:'Неизвестно', no_account:'Нет аккаунта', connection_failed:'Ошибка'};
    const colors = {ok:'border-green-500', needs_phone:'border-red-500', needs_follow:'border-yellow-500', unknown:'border-gray-500', no_account:'border-gray-600', connection_failed:'border-red-600'};

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

async function followBots() {
    const ch = document.getElementById('chat-channel').value.trim();
    if (!ch) return alert('Укажи канал');
    document.getElementById('chat-progress').classList.remove('hidden');
    document.getElementById('chat-results').innerHTML = '<div class="text-gray-400 text-sm animate-pulse">Подписываем ботов (20-40 сек между каждым)...</div>';

    const r = await fetch('{{ route("admin.tests.follow") }}', {
        method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf},
        body: JSON.stringify({channel: ch})
    });
    const d = await r.json();
    document.getElementById('chat-progress').classList.add('hidden');
    document.getElementById('chat-progress-bar').style.width = '100%';

    const icons = {followed:'✅', already_following:'⏩', failed:'❌'};
    document.getElementById('chat-results').innerHTML = (d.results||[]).map(r =>
        `<div class="flex items-center justify-between bg-gray-700 rounded-lg px-4 py-3">
            <span class="text-white">${r.bot} <span class="text-gray-400 text-sm">${r.account||''}</span></span>
            <span>${icons[r.status]||'❓'} ${r.status}</span>
        </div>`
    ).join('');
}

// ─── Viewers ───
let viewersChart = null;
let viewersTimer = null;
const viewersData = {labels:[], active:[], total:0, failed:0};

function initViewersChart() {
    const ctx = document.getElementById('viewers-chart').getContext('2d');
    viewersChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: viewersData.labels,
            datasets: [{
                label: 'Зрители',
                data: [],
                borderColor: '#eab308',
                backgroundColor: 'rgba(234,179,8,0.1)',
                tension: 0.4,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color:'#9ca3af' }, grid: { color:'#374151' } },
                y: { ticks: { color:'#9ca3af' }, grid: { color:'#374151' }, beginAtZero: true }
            }
        }
    });
}

function startViewers() {
    const ch    = document.getElementById('viewers-channel').value.trim();
    const count = document.getElementById('viewers-count').value;
    if (!ch) return alert('Укажи канал');
    if (!viewersChart) initViewersChart();
    // TODO: реальный запуск viewer модуля
    alert('⚠️ Модуль зрителей в разработке. Скоро!');
}

function stopViewers() {
    if (viewersTimer) clearInterval(viewersTimer);
    alert('Зрители остановлены');
}
</script>
@endpush
@endsection
