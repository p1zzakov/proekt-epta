<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountPool;
use App\Services\TwitchChatService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private AccountPool       $pool,
        private TwitchChatService $twitch,
    ) {}

    public function index(Request $request)
    {
        $query = Account::query();

        // Поиск
        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('twitch_id', 'like', "%{$search}%");
            });
        }

        // Фильтр по статусу
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Сортировка
        $sort      = in_array($request->get('sort'), ['username','messages_sent','messages_today','last_used_at','created_at'])
            ? $request->get('sort') : 'created_at';
        $dir       = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $accounts = $query->paginate(25)->withQueryString();

        $stats = array_merge($this->pool->stats(), [
            'messages_today' => Account::sum('messages_today'),
        ]);

        return view('admin.accounts.index', compact('accounts', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username'      => 'required|string|max:64|unique:accounts,username',
            'access_token'  => 'required|string',
            'refresh_token' => 'nullable|string',
            'note'          => 'nullable|string|max:255',
        ]);

        $account = Account::create([
            'username'      => $data['username'],
            'access_token'  => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? null,
            'note'          => $data['note'] ?? null,
            'status'        => 'available',
            'is_active'     => true,
        ]);

        // Валидируем токен сразу
        $valid = $this->twitch->validateToken($account);
        if (!$valid) {
            $account->markInvalid();
            return redirect()->route('admin.accounts.index')
                ->with('error', "Аккаунт {$account->username} добавлен, но токен невалиден!");
        }

        return redirect()->route('admin.accounts.index')
            ->with('success', "Аккаунт {$account->username} добавлен и токен подтверждён!");
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'access_token' => 'nullable|string',
            'note'         => 'nullable|string|max:255',
        ]);

        if (!empty($data['access_token'])) {
            $account->access_token = $data['access_token'];
            $account->status       = 'available';
            $account->is_active    = true;
            $account->save();

            $valid = $this->twitch->validateToken($account);
            if (!$valid) {
                $account->markInvalid();
                return redirect()->route('admin.accounts.index')
                    ->with('error', "Токен обновлён, но невалиден!");
            }
        }

        $account->note = $data['note'] ?? $account->note;
        $account->save();

        return redirect()->route('admin.accounts.index')
            ->with('success', "Аккаунт {$account->username} обновлён!");
    }

    public function destroy(Account $account)
    {
        $username = $account->username;
        $account->delete();

        return redirect()->route('admin.accounts.index')
            ->with('success', "Аккаунт {$username} удалён!");
    }

    // POST /admin/accounts/{id}/validate
    public function validateToken(Account $account)
    {
        $valid = $this->twitch->validateToken($account);

        if ($valid) {
            $account->is_active = true;
            $account->status    = 'available';
            $account->save();
        } else {
            $account->markInvalid();
        }

        return response()->json([
            'valid'   => $valid,
            'status'  => $account->fresh()->status,
        ]);
    }

    // POST /admin/accounts/{id}/reset
    public function reset(Account $account)
    {
        $account->is_active = true;
        $account->status    = 'available';
        $account->save();

        return redirect()->route('admin.accounts.index')
            ->with('success', "Статус аккаунта {$account->username} сброшен!");
    }
    // Проверить доступ к чату для одного аккаунта
    // POST /admin/accounts/{account}/check-chat
    public function checkChat(Request $request, Account $account)
    {
        $channel = $request->get('channel', 'chuckyturco');
        $status  = $this->twitch->checkChatAccess($account, $channel);

        if ($status === 'ok') {
            $account->phone_verified = true;
            $account->save();
        } elseif ($status === 'needs_phone') {
            $account->phone_verified = false;
            $account->save();
        }

        return response()->json([
            'status'         => $status,
            'phone_verified' => $account->fresh()->phone_verified,
        ]);
    }

    // Массовая проверка всех аккаунтов
    // POST /admin/accounts/bulk-check
    public function bulkCheck(Request $request)
    {
        $channel = $request->get('channel', 'chuckyturco');
        $limit   = $request->get('limit', 50);

        $results = $this->twitch->bulkCheckAccounts($channel, $limit);

        // Обновляем счётчик
        $verified = Account::where('phone_verified', true)->count();

        return response()->json([
            'results'  => $results,
            'verified' => $verified,
        ]);
    }

}
