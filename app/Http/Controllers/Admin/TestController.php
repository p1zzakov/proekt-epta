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

        // Запуск
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

        // Последние фразы из Redis
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

    // Тест чат-ботов
    public function chat(Request $request)
    {
        $channel = $request->input('channel');
        $chat    = new TwitchChatService();
        $results = [];

        $bots = Bot::with('account')->where('is_active', true)->get();

        foreach ($bots as $bot) {
            if (!$bot->account) {
                $results[] = ['bot' => $bot->name, 'account' => null, 'status' => 'no_account'];
                continue;
            }

            $status = $chat->checkChatAccess($bot->account, $channel);
            $results[] = [
                'bot'     => $bot->name,
                'account' => $bot->account->username,
                'style'   => $bot->style,
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
        $chat    = new TwitchChatService();

        $channelId = $chat->getChannelId($channel);
        if (!$channelId) {
            return response()->json(['error' => 'Channel not found'], 404);
        }

        $bots    = Bot::with('account')->where('is_active', true)->get();
        $results = [];

        foreach ($bots as $bot) {
            if (!$bot->account) continue;
            if ($bot->account->isFollowing($channel)) {
                $results[] = ['bot' => $bot->name, 'status' => 'already_following'];
                continue;
            }

            $ok = $chat->followChannel($bot->account, $channel, $channelId);
            $results[] = ['bot' => $bot->name, 'account' => $bot->account->username, 'status' => $ok ? 'followed' : 'failed'];

            if ($ok) {
                $delay = rand(20, 40);
                Cache::put("follow_progress:{$channel}", $results, 300);
                sleep($delay);
            }
        }

        return response()->json(['results' => $results, 'channel_id' => $channelId]);
    }

    // Статус подписки
    public function followStatus(Request $request)
    {
        $channel = $request->input('channel');
        $progress = Cache::get("follow_progress:{$channel}", []);
        return response()->json(['progress' => $progress]);
    }
}
