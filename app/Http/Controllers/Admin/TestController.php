<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bot;
use App\Services\TwitchChatService;
use App\Services\StreamContextService;
use App\Services\ResponseGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TestController extends Controller
{
    // Страница тестов
    public function index()
    {
        $bots = Bot::with('account')->get();
        return view('admin.tests.index', compact('bots'));
    }

    // Запуск/остановка Whisper
    public function whisper(Request $request)
    {
        $channel = $request->input('channel', 'gars_sem');
        $action  = $request->input('action', 'start');

        if ($action === 'stop') {
            exec("pkill -f 'stream_listener.py {$channel}'");
            return response()->json(['status' => 'stopped']);
        }

        exec("pkill -f 'stream_listener.py {$channel}' 2>/dev/null");
        sleep(1);
        $logFile = "/var/log/stream_listener_{$channel}.log";
        exec("nohup python3 /opt/stream_listener.py {$channel} > {$logFile} 2>&1 &");

        return response()->json(['status' => 'started', 'log' => $logFile]);
    }

    // Получение логов Whisper
    public function whisperLog(Request $request)
    {
        $channel = $request->input('channel', 'gars_sem');
        $logFile = "/var/log/stream_listener_{$channel}.log";
        $lines   = [];

        if (file_exists($logFile)) {
            $all   = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_slice($all, -30);
        }

        $speeches = [];
        $redisKey = "stream_speech:{$channel}";
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $raw = $redis->lrange($redisKey, 0, 9);
            foreach ($raw as $item) {
                $d = json_decode($item, true);
                if ($d) $speeches[] = $d;
            }
        } catch (\Exception $e) {}

        return response()->json([
            'lines'    => $lines,
            'speeches' => $speeches,
            'running'  => (bool) shell_exec("pgrep -f 'stream_listener.py {$channel}'"),
        ]);
    }

    // Тест чат-ботов — проверяем chatbot аккаунты напрямую
    public function chat(Request $request)
    {
        $channel = $request->input('channel');
        $chat    = new TwitchChatService();
        $results = [];

        // Берём chatbot аккаунты напрямую (не через ботов-персонажей)
        $accounts = \App\Models\Account::where('is_active', true)
            ->where('type', 'chatbot')
            ->where('phone_verified', true)
            ->get();

        foreach ($accounts as $account) {
            $status = $chat->checkChatAccess($account, $channel);
            $results[] = [
                'bot'     => $account->username,
                'account' => $account->username,
                'style'   => 'chatbot',
                'status'  => $status,
            ];
            usleep(300000);
        }

        return response()->json(['results' => $results]);
    }

    // Подписка ботов на канал
    public function followBots(Request $request)
    {
        $channel = $request->input('channel');
        $limit   = (int) $request->input('limit', 0); // 0 = все
        $chat    = new TwitchChatService();

        $channelId = $chat->getChannelId($channel);
        if (!$channelId) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        // Берём chatbot аккаунты которые ещё не подписаны
        $bots = \App\Models\Account::where('is_active', true)
            ->where('type', 'chatbot')
            ->where('phone_verified', true)
            ->get()
            ->filter(fn($account) => !$account->isFollowing($channel))
            ->values();

        // Если limit задан — берём только N ботов
        if ($limit > 0) {
            $bots = $bots->take($limit);
        }

        $results  = [];
        $followed = 0;

        foreach ($bots as $account) {
            $res = $chat->followChannel($account, $channel, $channelId);
            $results[] = [
                'bot'     => $account->username,
                'account' => $account->username,
                'status'  => $res['ok'] ? 'followed' : 'failed',
                'error'   => $res['error'] ?? null,
            ];

            if ($res['ok']) {
                $followed++;
                Cache::put("follow_progress:{$channel}", $results, 300);
                $delay = rand(20, 40);
                sleep($delay);
            } else {
                Cache::put("follow_progress:{$channel}", $results, 300);
            }
        }

        // Добавляем уже подписанных чатботов для полной картины
        $alreadyFollowing = \App\Models\Account::where('is_active', true)
            ->where('type', 'chatbot')
            ->where('phone_verified', true)
            ->get()
            ->filter(fn($account) => $account->isFollowing($channel))
            ->map(fn($account) => [
                'bot'     => $account->username,
                'account' => $account->username,
                'status'  => 'already_following',
            ])
            ->values()
            ->toArray();

        $allResults = array_merge($alreadyFollowing, $results);
        Cache::put("follow_progress:{$channel}", $allResults, 300);

        return response()->json([
            'results'    => $allResults,
            'channel_id' => $channelId,
            'followed'   => $followed,
            'total'      => count($allResults),
        ]);
    }

    // Живой чат канала — читаем IRC анонимно
    public function chatLive(Request $request)
    {
        $channel = $request->input('channel');
        if (!$channel) return response()->json(['messages' => []]);

        $chat = new TwitchChatService();
        $messages = $chat->readChatMessages($channel, 4);

        return response()->json(['messages' => $messages]);
    }

    // Отправить сообщение вручную от имени аккаунта
    public function chatSend(Request $request)
    {
        $channel   = $request->input('channel');
        $message   = $request->input('message');
        $accountId = $request->input('account_id');

        $account = \App\Models\Account::find($accountId);
        if (!$account) {
            return response()->json(['ok' => false, 'error' => 'Аккаунт не найден']);
        }

        $chat = new TwitchChatService();
        $sent = $chat->sendMessage($account, $channel, $message);

        return response()->json([
            'ok'      => $sent,
            'account' => $account->username,
            'message' => $message,
        ]);
    }

    // Статус подписки
    public function followStatus(Request $request)
    {
        $channel  = $request->input('channel');
        $progress = Cache::get("follow_progress:{$channel}", []);
        return response()->json(['progress' => $progress]);
    }

    // Запуск теста общения ботов через Queue
    public function botChatStart(Request $request)
    {
        $channel  = $request->input('channel');
        $botCount = (int) $request->input('bot_count', 3);
        $duration = (int) $request->input('duration', 5);
        $delay    = (int) $request->input('delay', 15);
        $mode     = $request->input('mode', 'real');
        $dryRun   = $request->input('dry_run') === true || $request->input('dry_run') === 'true' || $request->input('dry_run') === '1';

        if (!$channel) {
            return response()->json(['error' => 'Канал не указан'], 422);
        }

        $cacheKey = "bot_chat_test:{$channel}";

        // Сбрасываем старый лог
        Cache::put($cacheKey, [
            'status'   => 'running',
            'started'  => now()->toDateTimeString(),
            'channel'  => $channel,
            'mode'     => $mode,
            'duration' => $duration,
            'delay'    => $delay,
            'log'      => [],
        ], 3600);

        // Диспатчим Job в очередь (queue worker уже запущен через supervisor)
        \App\Jobs\RunChatBots::dispatch(
            $channel,
            $botCount,
            $duration * 60,
            $delay,
            $mode,
            $dryRun
        );

        return response()->json(['status' => 'started', 'channel' => $channel]);
    }

    // Остановка теста — ставим флаг, Job проверяет его в цикле
    public function botChatStop(Request $request)
    {
        $channel  = $request->input('channel');
        $cacheKey = "bot_chat_test:{$channel}";

        $data = Cache::get($cacheKey, []);
        $data['status'] = 'stopped';
        Cache::put($cacheKey, $data, 3600);

        return response()->json(['status' => 'stopped']);
    }

    // Лог теста общения ботов
    public function botChatLog(Request $request)
    {
        $channel  = $request->input('channel');
        $cacheKey = "bot_chat_test:{$channel}";
        $data     = Cache::get($cacheKey, ['status' => 'idle', 'log' => []]);

        return response()->json($data);
    }

    // Запуск накрутки зрителей
    public function viewersStart(Request $request)
    {
        $channel = $request->input('channel');
        $count   = (int) $request->input('count', 50);
        $rate    = (int) $request->input('rate', 7); // зрителей в минуту

        if (!$channel) return response()->json(['error' => 'Канал не указан'], 422);

        // Отправляем команду viewer_manager через Redis (он работает на хосте)
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $redis->rpush('viewer_commands', json_encode([
            'action'  => 'start',
            'channel' => $channel,
            'count'   => $count,
            'rate'    => $rate,
        ]));

        return response()->json(['status' => 'started', 'channel' => $channel, 'count' => $count]);
    }

    // Остановка накрутки зрителей
    public function viewersStop(Request $request)
    {
        $channel = $request->input('channel');
        // Отправляем команду остановки через Redis
        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->rpush('viewer_commands', json_encode([
                'action'  => 'stop',
                'channel' => $channel,
            ]));
        } catch (\Exception $e) {}

        return response()->json(['status' => 'stopped']);
    }

    // Статистика зрителей
    public function viewersStats(Request $request)
    {
        $channel = $request->input('channel');

        try {
            $redis = new \Redis();
            $redis->connect('127.0.0.1', 6379);
            $stats = $redis->hgetall("viewer_bot:{$channel}");
        } catch (\Exception $e) {
            $stats = [];
        }

        $running = (bool) shell_exec("pgrep -f 'viewer_bot.py {$channel}'");

        return response()->json([
            'running' => $running,
            'active'  => (int) ($stats['active'] ?? 0),
            'total'   => (int) ($stats['total'] ?? 0),
            'updated' => (int) ($stats['updated'] ?? 0),
        ]);
    }
}
