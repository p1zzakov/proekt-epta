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

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('twitch_id', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Фильтр по типу
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Фильтр по телефону
        if ($request->has('phone') && $request->get('phone') !== '') {
            $query->where('phone_verified', (bool) $request->get('phone'));
        }

        $sort = in_array($request->get('sort'), ['username','messages_sent','messages_today','last_used_at','created_at'])
            ? $request->get('sort') : 'created_at';
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        // Быстрый ответ для автообновления счётчиков
        if ($request->header('X-Requested-With') === 'XMLHttpRequest' && $request->get('counts_only')) {
            return response()->json([
                'viewers'  => Account::where('type', 'viewer')->count(),
                'chatbots' => Account::where('type', 'chatbot')->count(),
            ]);
        }

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
            'type'         => 'nullable|in:viewer,chatbot',
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

        if (isset($data['type'])) {
            $account->type = $data['type'];
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

    // POST /admin/accounts/{account}/toggle
    public function toggle(Account $account)
    {
        $account->is_active = !$account->is_active;
        $account->status    = $account->is_active ? 'available' : 'invalid';
        $account->save();

        $state = $account->is_active ? 'активирован' : 'деактивирован';
        return redirect()->route('admin.accounts.index')
            ->with('success', "Аккаунт {$account->username} {$state}!");
    }

    // GET /admin/accounts/import — перенаправление на AccountImportController
    public function importForm()
    {
        $availableProxies = \App\Models\Proxy::available()->count();
        return view('admin.accounts.import', compact('availableProxies'));
    }

    // POST /admin/accounts/import
    public function import(Request $request)
    {
        return app(\App\Http\Controllers\Admin\AccountImportController::class)->import($request);
    }

    // POST /admin/accounts/{account}/validate
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

    // POST /admin/accounts/{account}/check-phone
    public function checkPhone(Request $request, Account $account)
    {
        $channel = $request->get('channel', $account->username);
        $status  = $this->twitch->checkPhoneVerified($account, $channel);

        if ($status === 'ok') {
            $account->phone_verified = true;
            $account->type = 'chatbot';
            $account->save();
        } elseif ($status === 'needs_phone') {
            $account->phone_verified = false;
            $account->type = 'viewer';
            $account->save();
        }

        return response()->json([
            'status'         => $status,
            'phone_verified' => $account->fresh()->phone_verified,
            'type'           => $account->fresh()->type,
        ]);
    }

    // POST /admin/accounts/bulk-phone-check — массовая проверка телефонов
    public function bulkPhoneCheck(Request $request)
    {
        $limit   = (int) $request->get('limit', 50);
        $channel = $request->get('channel', 'surprise011');

        $accounts = Account::where('status', 'available')
            ->where('is_active', true)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        $results = ['ok' => 0, 'needs_phone' => 0, 'needs_follow' => 0, 'invalid_token' => 0, 'unknown' => 0];

        foreach ($accounts as $account) {
            $status = $this->twitch->checkPhoneVerified($account, $channel);
            $results[$status] = ($results[$status] ?? 0) + 1;
            usleep(500000); // 0.5 сек между проверками
        }

        $chatbots = Account::where('type', 'chatbot')->count();
        $viewers  = Account::where('type', 'viewer')->count();

        return response()->json([
            'results'  => $results,
            'chatbots' => $chatbots,
            'viewers'  => $viewers,
        ]);
    }

    // POST /admin/accounts/bulk-check
    public function bulkCheck(Request $request)
    {
        $channel = $request->get('channel', 'chuckyturco');
        $limit   = $request->get('limit', 50);

        $results = $this->twitch->bulkCheckAccounts($channel, $limit);

        $verified = Account::where('phone_verified', true)->count();

        return response()->json([
            'results'  => $results,
            'verified' => $verified,
        ]);
    }
}