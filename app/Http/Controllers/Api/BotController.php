<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BotController extends Controller
{
    // GET /api/bots
    public function index(): JsonResponse
    {
        $bots = Bot::orderBy('name')->get();

        return response()->json([
            'data'  => $bots,
            'total' => $bots->count(),
        ]);
    }

    // POST /api/bots
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:64|unique:bots,name',
            'style'     => 'required|string|in:sarcastic,hype,toxic,silent,memer,analyst,noob,veteran,hater,neutral',
            'knowledge' => 'nullable|array',
            'knowledge.*' => 'string|max:64',
            'toxicity'  => 'nullable|numeric|min:0|max:1',
            'verbosity' => 'nullable|numeric|min:0|max:1',
            'weight'    => 'nullable|integer|min:1|max:100',
        ]);

        $bot = Bot::create([
            'name'      => $data['name'],
            'style'     => $data['style'],
            'knowledge' => $data['knowledge'] ?? [],
            'toxicity'  => $data['toxicity'] ?? 0.0,
            'verbosity' => $data['verbosity'] ?? 0.5,
            'weight'    => $data['weight'] ?? 10,
        ]);

        return response()->json(['data' => $bot], 201);
    }

    // GET /api/bots/{id}
    public function show(Bot $bot): JsonResponse
    {
        return response()->json(['data' => $bot]);
    }

    // PUT /api/bots/{id}
    public function update(Request $request, Bot $bot): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:64|unique:bots,name,' . $bot->id,
            'style'     => 'sometimes|string|in:sarcastic,hype,toxic,silent,memer,analyst,noob,veteran,hater,neutral',
            'knowledge' => 'nullable|array',
            'knowledge.*' => 'string|max:64',
            'toxicity'  => 'nullable|numeric|min:0|max:1',
            'verbosity' => 'nullable|numeric|min:0|max:1',
            'weight'    => 'nullable|integer|min:1|max:100',
        ]);

        $bot->update($data);

        return response()->json(['data' => $bot]);
    }

    // DELETE /api/bots/{id}
    public function destroy(Bot $bot): JsonResponse
    {
        $bot->delete();

        return response()->json(['message' => "Бот {$bot->name} удалён"]);
    }

    // POST /api/bots/{id}/reset-cooldown
    public function resetCooldown(Bot $bot): JsonResponse
    {
        $bot->cooldown_until = null;
        $bot->save();

        return response()->json(['message' => "Кулдаун бота {$bot->name} сброшен"]);
    }
}
