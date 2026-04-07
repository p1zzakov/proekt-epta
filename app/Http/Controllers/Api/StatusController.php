<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use App\Services\ResponseGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class StatusController extends Controller
{
    public function __invoke(ResponseGenerator $generator): JsonResponse
    {
        $checks = [];

        // Ollama
        $checks['ollama'] = $generator->isAvailable()
            ? ['ok' => true,  'message' => 'Ollama доступна']
            : ['ok' => false, 'message' => 'Ollama недоступна'];

        // База данных
        try {
            DB::connection()->getPdo();
            $botCount = Bot::count();
            $checks['database'] = ['ok' => true, 'message' => "БД доступна, ботов: {$botCount}"];
        } catch (\Exception $e) {
            $checks['database'] = ['ok' => false, 'message' => 'БД недоступна: ' . $e->getMessage()];
        }

        // Redis
        try {
            Redis::ping();
            $checks['redis'] = ['ok' => true, 'message' => 'Redis доступен'];
        } catch (\Exception $e) {
            $checks['redis'] = ['ok' => false, 'message' => 'Redis недоступен: ' . $e->getMessage()];
        }

        // Боты на кулдауне
        $onCooldown = Bot::whereNotNull('cooldown_until')
            ->where('cooldown_until', '>', now())
            ->count();

        $checks['bots'] = [
            'ok'          => true,
            'total'       => Bot::count(),
            'on_cooldown' => $onCooldown,
            'available'   => Bot::count() - $onCooldown,
        ];

        $allOk = collect($checks)->every(fn($c) => $c['ok']);

        return response()->json([
            'status' => $allOk ? 'ok' : 'degraded',
            'checks' => $checks,
            'model'  => config('bot.ollama_model'),
            'time'   => now()->toIso8601String(),
        ], $allOk ? 200 : 503);
    }
}
