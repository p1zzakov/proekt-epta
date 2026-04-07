<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function index()
    {
        $bots = Bot::orderBy('name')->get();
        return view('admin.bots.index', compact('bots'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:64|unique:bots,name',
            'style'     => 'required|string|in:sarcastic,hype,toxic,silent,memer,analyst,noob,veteran,hater,neutral',
            'knowledge' => 'nullable|string',
            'toxicity'  => 'nullable|numeric|min:0|max:1',
            'verbosity' => 'nullable|numeric|min:0|max:1',
            'weight'    => 'nullable|integer|min:1|max:100',
        ]);

        Bot::create([
            'name'      => $data['name'],
            'style'     => $data['style'],
            'knowledge' => $this->parseKnowledge($data['knowledge'] ?? ''),
            'toxicity'  => $data['toxicity'] ?? 0.2,
            'verbosity' => $data['verbosity'] ?? 0.5,
            'weight'    => $data['weight'] ?? 10,
        ]);

        return redirect()->route('admin.bots.index')
            ->with('success', "Бот {$data['name']} создан!");
    }

    public function update(Request $request, Bot $bot)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:64|unique:bots,name,' . $bot->id,
            'style'     => 'required|string|in:sarcastic,hype,toxic,silent,memer,analyst,noob,veteran,hater,neutral',
            'knowledge' => 'nullable|string',
            'toxicity'  => 'nullable|numeric|min:0|max:1',
            'verbosity' => 'nullable|numeric|min:0|max:1',
            'weight'    => 'nullable|integer|min:1|max:100',
        ]);

        $bot->update([
            'name'      => $data['name'],
            'style'     => $data['style'],
            'knowledge' => $this->parseKnowledge($data['knowledge'] ?? ''),
            'toxicity'  => $data['toxicity'] ?? $bot->toxicity,
            'verbosity' => $data['verbosity'] ?? $bot->verbosity,
            'weight'    => $data['weight'] ?? $bot->weight,
        ]);

        return redirect()->route('admin.bots.index')
            ->with('success', "Бот {$bot->name} обновлён!");
    }

    public function destroy(Bot $bot)
    {
        $name = $bot->name;
        $bot->delete();

        return redirect()->route('admin.bots.index')
            ->with('success', "Бот {$name} удалён!");
    }

    public function resetCooldown(Bot $bot)
    {
        $bot->cooldown_until = null;
        $bot->save();

        return redirect()->route('admin.bots.index')
            ->with('success', "Кулдаун бота {$bot->name} сброшен!");
    }

    private function parseKnowledge(string $str): array
    {
        if (empty(trim($str))) return [];

        return array_values(array_filter(
            array_map('trim', explode(',', $str))
        ));
    }
}
