<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\AccountPool;
use App\Services\TwitchChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct(
        private AccountPool      $pool,
        private TwitchChatService $twitch,
    ) {}

    // GET /api/accounts
    public function index(): JsonResponse
    {
        $accounts = Account::orderBy('username')->get();
        return response()->json([
            'data'  => $accounts,
            'stats' => $this->pool->stats(),
        ]);
    }

    // POST /api/accounts — добавить аккаунт в пул
    public function store(Request $request): JsonResponse
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

        // Сразу валидируем токен и подтягиваем twitch_id
        $valid = $this->twitch->validateToken($account);

        if (!$valid) {
            $account->markInvalid();
            return response()->json([
                'data'    => $account->fresh(),
                'warning' => 'Токен невалиден — аккаунт помечен как invalid',
            ], 201);
        }

        return response()->json(['data' => $account->fresh()], 201);
    }

    // GET /api/accounts/{id}
    public function show(Account $account): JsonResponse
    {
        return response()->json(['data' => $account]);
    }

    // DELETE /api/accounts/{id}
    public function destroy(Account $account): JsonResponse
    {
        $account->delete();
        return response()->json(['message' => "Аккаунт {$account->username} удалён"]);
    }

    // POST /api/accounts/{id}/validate — проверить токен
    public function checkToken(Account $account): JsonResponse
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
            'account' => $account->fresh(),
        ]);
    }

    // GET /api/accounts/stats — статистика пула
    public function stats(): JsonResponse
    {
        return response()->json($this->pool->stats());
    }
}