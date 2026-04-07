<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proxy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProxyController extends Controller
{
    public function index()
    {
        $proxies = Proxy::with('account')->orderBy('status')->paginate(50);
        $stats = [
            'total'     => Proxy::count(),
            'available' => Proxy::where('status', 'available')->count(),
            'in_use'    => Proxy::where('status', 'in_use')->count(),
            'dead'      => Proxy::where('status', 'dead')->count(),
        ];
        return view('admin.proxies.index', compact('proxies', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'     => 'required|in:http,https,socks5',
            'host'     => 'required|string',
            'port'     => 'required|integer|min:1|max:65535',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'note'     => 'nullable|string',
        ]);

        Proxy::create($data);

        return redirect()->route('admin.proxies.index')
            ->with('success', 'Прокси добавлен!');
    }

    public function destroy(Proxy $proxy)
    {
        $proxy->delete();
        return redirect()->route('admin.proxies.index')
            ->with('success', 'Прокси удалён!');
    }

    // POST /admin/proxies/import
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:txt',
            'type' => 'required|in:http,https,socks5',
        ]);

        $lines  = file($request->file('file')->getPathname(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $added  = 0;
        $errors = 0;

        foreach ($lines as $line) {
            $parsed = Proxy::parseString($line, $request->type);
            if (!$parsed) { $errors++; continue; }

            Proxy::firstOrCreate(
                ['host' => $parsed['host'], 'port' => $parsed['port']],
                $parsed
            );
            $added++;
        }

        return redirect()->route('admin.proxies.index')
            ->with('success', "Импортировано: {$added}, ошибок: {$errors}");
    }

    // POST /admin/proxies/{proxy}/check
    public function check(Proxy $proxy)
    {
        $start = microtime(true);
        $alive = false;
        $ms    = null;

        try {
            $response = Http::timeout(10)
                ->withOptions(['proxy' => $proxy->toUrl()])
                ->get('https://api.ipify.org?format=json');

            if ($response->successful()) {
                $alive = true;
                $ms    = round((microtime(true) - $start) * 1000);
            }
        } catch (\Exception) {}

        $proxy->update([
            'status'           => $alive ? 'available' : 'dead',
            'last_checked_at'  => now(),
            'response_time_ms' => $ms,
            'fail_count'       => $alive ? 0 : $proxy->fail_count + 1,
        ]);

        return response()->json([
            'alive'            => $alive,
            'response_time_ms' => $ms,
        ]);
    }
}
