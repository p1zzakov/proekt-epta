<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Proxy;
use App\Services\TwitchChatService;
use Illuminate\Http\Request;

class AccountImportController extends Controller
{
    public function __construct(private TwitchChatService $twitch) {}

    public function show()
    {
        $availableProxies = Proxy::available()->count();
        return view('admin.accounts.import', compact('availableProxies'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:txt',
            'assign_proxies'  => 'nullable|boolean',
            'note'            => 'nullable|string|max:255',
        ]);

        $lines          = file($request->file('file')->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $assignProxies  = $request->boolean('assign_proxies', true);
        $note           = $request->input('note');

        $added      = 0;
        $invalid    = 0;
        $duplicates = 0;

        // Берём пул доступных прокси
        $proxyPool = $assignProxies
            ? Proxy::available()->orderBy('last_used_at')->get()
            : collect();
        $proxyIndex = 0;

        foreach ($lines as $line) {
            $token = trim($line);
            if (empty($token)) continue;

            // Проверяем дубликат
            if (Account::where('access_token', $token)->exists()) {
                $duplicates++;
                continue;
            }

            // Создаём временный аккаунт для проверки
            $account = new Account([
                'username'     => 'pending_' . substr($token, 0, 8),
                'access_token' => $token,
                'status'       => 'available',
                'is_active'    => true,
                'note'         => $note,
            ]);

            // Назначаем прокси
            if ($assignProxies && isset($proxyPool[$proxyIndex])) {
                $account->proxy_id = $proxyPool[$proxyIndex]->id;
                $proxyIndex++;
            }

            $account->save();

            // Валидируем токен
            $valid = $this->twitch->validateToken($account);

            if (!$valid) {
                $account->markInvalid();
                $invalid++;
            } else {
                $added++;
            }
        }

        return redirect()->route('admin.accounts.index')
            ->with('import_result', compact('added', 'invalid', 'duplicates'))
            ->with('success', "Импорт завершён! Добавлено: {$added}, невалидных: {$invalid}, дубликатов: {$duplicates}");
    }
}
