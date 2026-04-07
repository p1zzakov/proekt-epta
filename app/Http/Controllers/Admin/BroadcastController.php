<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBroadcast;
use App\Models\Broadcast;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BroadcastController extends Controller
{
    public function index()
    {
        $broadcasts = Broadcast::with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total'   => Broadcast::count(),
            'sending' => Broadcast::where('status', 'sending')->count(),
            'done'    => Broadcast::where('status', 'done')->count(),
            'clients' => Client::where('is_active', true)->count(),
        ];

        // Для ручного выбора — список клиентов
        $clients = Client::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'plan']);

        return view('admin.broadcast', compact('broadcasts', 'stats', 'clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'message'         => 'required|string',
            'send_email'      => 'nullable|boolean',
            'send_telegram'   => 'nullable|boolean',
            'send_push'       => 'nullable|boolean',
            'audience'        => 'required|in:all,plan,status,manual',
            'audience_plan'   => 'nullable|string|in:free,basic,pro,enterprise',
            'audience_status' => 'nullable|string|in:active,suspended,banned',
            'audience_ids'    => 'nullable|array',
            'audience_ids.*'  => 'integer|exists:clients,id',
        ]);

        $broadcast = Broadcast::create([
            'title'           => $data['title'],
            'message'         => $data['message'],
            'send_email'      => $request->boolean('send_email'),
            'send_telegram'   => $request->boolean('send_telegram'),
            'send_push'       => $request->boolean('send_push'),
            'audience'        => $data['audience'],
            'audience_plan'   => $data['audience_plan'] ?? null,
            'audience_status' => $data['audience_status'] ?? null,
            'audience_ids'    => $data['audience_ids'] ?? null,
            'status'          => 'draft',
            'created_by'      => Auth::id(),
        ]);

        // Отправляем в очередь
        SendBroadcast::dispatch($broadcast->id);

        return redirect()->route('admin.broadcast')
            ->with('success', "Рассылка \"{$broadcast->title}\" запущена!");
    }

    public function preview(Request $request)
    {
        $audience = $request->get('audience', 'all');
        $plan     = $request->get('audience_plan');
        $status   = $request->get('audience_status');
        $ids      = $request->get('audience_ids', []);

        $query = Client::where('is_active', true);

        $count = match($audience) {
            'plan'   => $query->where('plan', $plan)->count(),
            'status' => $query->where('status', $status)->count(),
            'manual' => count($ids),
            default  => $query->count(),
        };

        return response()->json(['count' => $count]);
    }
}
