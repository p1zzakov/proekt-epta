<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Bot;
use App\Services\ResponseGenerator;

class DashboardController extends Controller
{
    public function __invoke(ResponseGenerator $generator)
    {
        $stats = [
            'bots' => [
                'total'       => Bot::count(),
                'available'   => Bot::where(function($q) {
                    $q->whereNull('cooldown_until')->orWhere('cooldown_until', '<', now());
                })->count(),
                'on_cooldown' => Bot::where('cooldown_until', '>', now())->count(),
            ],
            'accounts' => [
                'total'     => Account::count(),
                'available' => Account::where('is_active', true)->where('status', 'available')->count(),
                'busy'      => Account::where('status', 'busy')->count(),
                'banned'    => Account::where('status', 'banned')->count(),
            ],
            'messages_today' => Account::sum('messages_today'),
            'messages_total' => Account::sum('messages_sent'),
            'ollama_ok'      => $generator->isAvailable(),
        ];

        $bots     = Bot::orderBy('name')->get();
        $accounts = Account::orderBy('username')->limit(10)->get();

        return view('admin.dashboard', compact('stats', 'bots', 'accounts'));
    }
}
