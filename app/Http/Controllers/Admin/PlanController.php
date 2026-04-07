<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('sort_order')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug'            => 'required|string|max:32|unique:plans,slug|regex:/^[a-z_]+$/',
            'name'            => 'required|string|max:64',
            'price'           => 'required|numeric|min:0',
            'billing_period'  => 'required|in:hour,day,week,month,stream',
            'min_units'       => 'nullable|integer|min:1',
            'max_units'       => 'nullable|integer|min:0',
            'description'     => 'nullable|string|max:255',
            'features'        => 'nullable|string',
            'button_text'     => 'nullable|string|max:64',
            'badge'           => 'nullable|string|max:64',
            'sort_order'      => 'nullable|integer|min:0',
            'bot_mode'        => 'required|in:viewers,manual,ai',
            'max_viewers'     => 'nullable|integer|min:0',
            'max_bots'        => 'nullable|integer|min:0',
            'max_streams'     => 'nullable|integer|min:1',
            'stream_duration' => 'nullable|integer|min:0',
        ]);

        Plan::create([
            'slug'            => $data['slug'],
            'name'            => $data['name'],
            'price'           => $data['price'],
            'billing_period'  => $data['billing_period'],
            'min_units'       => $data['min_units'] ?? 1,
            'max_units'       => $data['max_units'] ?? 0,
            'description'     => $data['description'] ?? null,
            'features'        => $this->parseFeatures($data['features'] ?? ''),
            'button_text'     => $data['button_text'] ?? 'Начать',
            'badge'           => $data['badge'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 0,
            'bot_mode'        => $data['bot_mode'],
            'max_viewers'     => $data['max_viewers'] ?? 0,
            'max_bots'        => $data['bot_mode'] === 'viewers' ? 0 : ($data['max_bots'] ?? 0),
            'max_streams'     => $data['max_streams'] ?? 1,
            'stream_duration' => $data['stream_duration'] ?? 4,
            'is_popular'      => $request->boolean('is_popular'),
            'is_active'       => $request->boolean('is_active', true),
        ]);

        Cache::forget('plans_active');

        return redirect()->route('admin.plans.index')
            ->with('success', "Тариф {$data['name']} создан!");
    }

    public function update(Request $request, Plan $plan)
    {
        $data = $request->validate([
            'name'            => 'required|string|max:64',
            'price'           => 'required|numeric|min:0',
            'period'          => 'nullable|string|max:32',
            'description'     => 'nullable|string|max:255',
            'features'        => 'nullable|string',
            'button_text'     => 'nullable|string|max:64',
            'badge'           => 'nullable|string|max:64',
            'sort_order'      => 'nullable|integer|min:0',
            'bot_mode'        => 'required|in:viewers,manual,ai',
            'max_viewers'     => 'nullable|integer|min:0',
            'max_bots'        => 'nullable|integer|min:0',
            'max_streams'     => 'nullable|integer|min:1',
            'stream_duration' => 'nullable|integer|min:0',
        ]);

        $plan->update([
            'name'            => $data['name'],
            'price'           => $data['price'],
            'billing_period'  => $data['billing_period'],
            'min_units'       => $data['min_units'] ?? 1,
            'max_units'       => $data['max_units'] ?? 0,
            'description'     => $data['description'] ?? null,
            'features'        => $this->parseFeatures($data['features'] ?? ''),
            'button_text'     => $data['button_text'] ?? 'Начать',
            'badge'           => $data['badge'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 0,
            'bot_mode'        => $data['bot_mode'],
            'max_viewers'     => $data['max_viewers'] ?? 0,
            'max_bots'        => $data['bot_mode'] === 'viewers' ? 0 : ($data['max_bots'] ?? 0),
            'max_streams'     => $data['max_streams'] ?? 1,
            'stream_duration' => $data['stream_duration'] ?? 4,
            'is_popular'      => $request->boolean('is_popular'),
            'is_active'       => $request->boolean('is_active', true),
        ]);

        Cache::forget('plans_active');

        return redirect()->route('admin.plans.index')
            ->with('success', "Тариф {$plan->name} обновлён!");
    }

    public function destroy(Plan $plan)
    {
        $name = $plan->name;
        $plan->delete();
        Cache::forget('plans_active');

        return redirect()->route('admin.plans.index')
            ->with('success', "Тариф {$name} удалён!");
    }

    private function parseFeatures(string $str): array
    {
        if (empty(trim($str))) return [];
        return array_values(array_filter(array_map('trim', explode("\n", $str))));
    }
}