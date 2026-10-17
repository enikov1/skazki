<?php

namespace App\Http\Controllers;

use App\Models\InteractiveGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InteractiveGameAttemptController extends Controller
{
    public function store(Request $request, string $type, string $slug): JsonResponse
    {
        abort_unless(in_array($type, ['differences', 'mosaics'], true), 404);

        $game = InteractiveGame::published()
            ->where('type', $type)
            ->where('slug', $slug)
            ->firstOrFail();

        $data = $request->validate([
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:14400'],
        ]);

        $attempt = $request->user()->interactiveGameAttempts()->create([
            'interactive_game_id' => $game->id,
            'duration_seconds' => $data['duration_seconds'],
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Результат сохранён в истории профиля.',
            'attempt' => ['id' => $attempt->id, 'duration_seconds' => $attempt->duration_seconds],
        ], 201);
    }
}
