<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BotTypeController extends Controller
{
    public function index()
    {
        $types = BotType::orderBy('name')->get();
        return view('admin.bot-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => 'required|string|max:32|unique:bot_types,name|regex:/^[a-z_]+$/',
            'label'             => 'required|string|max:64',
            'system_prompt'     => 'required|string',
            'behavior_prompt'   => 'nullable|string',
            'emoji_instruction' => 'nullable|string',
            'emotes'            => 'nullable|string',
            'emoji'             => 'nullable|string',
            'ru_words'          => 'nullable|string',
            'is_active'         => 'nullable|boolean',
        ]);

        BotType::create([
            'name'              => $data['name'],
            'label'             => $data['label'],
            'system_prompt'     => $data['system_prompt'],
            'behavior_prompt'   => $data['behavior_prompt'] ?? '',
            'emoji_instruction' => $data['emoji_instruction'] ?? '',
            'emotes'            => $this->parseList($data['emotes'] ?? ''),
            'emoji'             => $this->parseList($data['emoji'] ?? ''),
            'ru_words'          => $this->parseList($data['ru_words'] ?? ''),
            'is_active'         => $request->boolean('is_active', true),
        ]);

        Cache::forget("bot_type:{$data['name']}");

        return redirect()->route('admin.bot-types.index')
            ->with('success', "Тип '{$data['label']}' создан!");
    }

    public function update(Request $request, BotType $botType)
    {
        $data = $request->validate([
            'label'             => 'required|string|max:64',
            'system_prompt'     => 'required|string',
            'behavior_prompt'   => 'nullable|string',
            'emoji_instruction' => 'nullable|string',
            'emotes'            => 'nullable|string',
            'emoji'             => 'nullable|string',
            'ru_words'          => 'nullable|string',
            'is_active'         => 'nullable|boolean',
        ]);

        $botType->update([
            'label'             => $data['label'],
            'system_prompt'     => $data['system_prompt'],
            'behavior_prompt'   => $data['behavior_prompt'] ?? '',
            'emoji_instruction' => $data['emoji_instruction'] ?? '',
            'emotes'            => $this->parseList($data['emotes'] ?? ''),
            'emoji'             => $this->parseList($data['emoji'] ?? ''),
            'ru_words'          => $this->parseList($data['ru_words'] ?? ''),
            'is_active'         => $request->boolean('is_active', true),
        ]);

        Cache::forget("bot_type:{$botType->name}");

        return redirect()->route('admin.bot-types.index')
            ->with('success', "Тип '{$botType->label}' обновлён!");
    }

    public function destroy(BotType $botType)
    {
        if ($botType->bots()->count() > 0) {
            return redirect()->route('admin.bot-types.index')
                ->with('error', "Нельзя удалить — есть боты с этим типом!");
        }

        Cache::forget("bot_type:{$botType->name}");
        $label = $botType->label;
        $botType->delete();

        return redirect()->route('admin.bot-types.index')
            ->with('success', "Тип '{$label}' удалён!");
    }

    private function parseList(string $str): array
    {
        if (empty(trim($str))) return [];
        return array_values(array_filter(array_map('trim', explode(',', $str))));
    }
}
