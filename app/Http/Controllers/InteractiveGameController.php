<?php

namespace App\Http\Controllers;

use App\Models\InteractiveGame;
use Illuminate\View\View;

class InteractiveGameController extends Controller
{
    public function differencesIndex(): View
    {
        return $this->indexForType('differences');
    }

    public function differencesShow(string $slug): View
    {
        return $this->showForType('differences', $slug);
    }

    public function mosaicsIndex(): View
    {
        return $this->indexForType('mosaics');
    }

    public function mosaicsShow(string $slug): View
    {
        return $this->showForType('mosaics', $slug);
    }

    public function index(string $type): View
    {
        return $this->indexForType($type);
    }

    private function indexForType(string $type): View
    {
        abort_unless(in_array($type, ['differences', 'mosaics'], true), 404);
        $games = InteractiveGame::published()->where('type', $type)->orderBy('title')->paginate(12);
        $label = $type === 'differences' ? 'Найди отличия' : 'Мозаики';
        return view('games.interactive-index', compact('games', 'type', 'label'));
    }

    public function show(string $type, string $slug): View
    {
        return $this->showForType($type, $slug);
    }

    private function showForType(string $type, string $slug): View
    {
        abort_unless(in_array($type, ['differences', 'mosaics'], true), 404);
        $game = InteractiveGame::published()->where('type', $type)->where('slug', $slug)->firstOrFail();
        return view('games.play', compact('game', 'type'));
    }
}
