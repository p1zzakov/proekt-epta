<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = Client::query();

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telegram', 'like', "%{$search}%")
                  ->orWhere('twitch_channel', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($plan = $request->get('plan')) {
            $query->where('plan', $plan);
        }

        $sort = in_array($request->get('sort'), ['created_at','balance','name','last_login_at'])
            ? $request->get('sort') : 'created_at';
        $dir  = $request->get('dir') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        $clients = $query->paginate(25)->withQueryString();

        $stats = [
            'total'         => Client::count(),
            'active'        => Client::where('status', 'active')->count(),
            'with_plan'     => Client::where('plan', '!=', 'free')->count(),
            'total_balance' => Client::sum('balance'),
            'new_today'     => Client::whereDate('created_at', today())->count(),
        ];

        return view('admin.users.index', compact('clients', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:64',
            'email'          => 'required|email|unique:clients,email',
            'password'       => 'required|string|min:8',
            'telegram'       => 'nullable|string|max:64',
            'twitch_channel' => 'nullable|string|max:64',
            'plan'           => 'nullable|string|in:free,basic,pro,enterprise',
            'notes'          => 'nullable|string',
        ]);

        Client::create([
            'name'           => $data['name'],
            'email'          => $data['email'],
            'password'       => Hash::make($data['password']),
            'telegram'       => $data['telegram'] ?? null,
            'twitch_channel' => $data['twitch_channel'] ?? null,
            'plan'           => $data['plan'] ?? 'free',
            'notes'          => $data['notes'] ?? null,
            'email_verified_at' => now(), // Созданные админом — сразу верифицированы
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Клиент {$data['name']} создан!");
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:64',
            'telegram'         => 'nullable|string|max:64',
            'twitch_channel'   => 'nullable|string|max:64',
            'plan'             => 'nullable|string|in:free,basic,pro,enterprise',
            'plan_expires_at'  => 'nullable|date',
            'password'         => 'nullable|string|min:8',
            'notes'            => 'nullable|string',
        ]);

        $update = [
            'name'           => $data['name'],
            'telegram'       => $data['telegram'] ?? null,
            'twitch_channel' => $data['twitch_channel'] ?? null,
            'plan'           => $data['plan'] ?? $client->plan,
            'plan_expires_at'=> $data['plan_expires_at'] ?? null,
            'notes'          => $data['notes'] ?? null,
        ];

        if (!empty($data['password'])) {
            $update['password'] = Hash::make($data['password']);
        }

        $client->update($update);

        return redirect()->route('admin.users.index')
            ->with('success', "Клиент {$client->name} обновлён!");
    }

    public function deposit(Request $request, Client $client)
    {
        $data = $request->validate([
            'type'        => 'required|in:deposit,withdraw,bonus,refund',
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'reference'   => 'nullable|string|max:128',
        ]);

        $adminId = Auth::id();

        if ($data['type'] === 'withdraw') {
            $result = $client->withdraw($data['amount'], $data['description'] ?? '');
            if (!$result) {
                return redirect()->route('admin.users.index')
                    ->with('error', "Недостаточно средств на балансе!");
            }
        } elseif ($data['type'] === 'bonus') {
            $client->addBonus($data['amount'], $data['description'] ?? '', $adminId);
        } else {
            $client->deposit($data['amount'], $data['description'] ?? '', $data['reference'] ?? '', $adminId);
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Операция проведена! Новый баланс: \${$client->fresh()->balance}");
    }

    public function toggle(Client $client)
    {
        if ($client->status === 'banned') {
            $client->status    = 'active';
            $client->is_active = true;
        } else {
            $client->status    = 'banned';
            $client->is_active = false;
        }
        $client->save();

        $msg = $client->status === 'banned' ? "забанен" : "разбанен";
        return redirect()->route('admin.users.index')
            ->with('success', "Клиент {$client->name} {$msg}!");
    }

    public function info(Client $client)
    {
        return response()->json([
            'client'       => $client,
            'transactions' => $client->transactions()
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(),
        ]);
    }
}
