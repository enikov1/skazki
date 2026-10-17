<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\InteractiveGame;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class InteractiveGameAdminController extends Controller
{
    use RespondsToAjax;

    public function index(): View
    {
        Gate::authorize('viewAny', InteractiveGame::class);
        return view('admin.games.index', ['games' => InteractiveGame::with(['creator:id,name', 'updater:id,name'])->latest()->paginate(20)]);
    }

    public function create(): View
    {
        Gate::authorize('create', InteractiveGame::class);
        return view('admin.games.form', ['game' => new InteractiveGame()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        Gate::authorize('create', InteractiveGame::class);
        $game = InteractiveGame::create($this->validated($request) + ['created_by' => $request->user()->id, 'updated_by' => $request->user()->id]);
        return $this->respond($request, 'Игра создана.', route('admin.games.edit', $game));
    }

    public function edit(InteractiveGame $game): View
    {
        Gate::authorize('update', $game);
        return view('admin.games.form', compact('game'));
    }

    public function update(Request $request, InteractiveGame $game): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $game);
        $game->update($this->validated($request, $game) + ['updated_by' => $request->user()->id]);
        return $this->respond($request, 'Изменения игры сохранены.', route('admin.games.edit', $game));
    }

    public function destroy(Request $request, InteractiveGame $game): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $game);
        $game->delete();
        return $this->respond($request, 'Игра удалена.', route('admin.games.index'));
    }

    private function validated(Request $request, ?InteractiveGame $game = null): array
    {
        if (is_string($request->input('slug')) && filled($request->input('slug'))) $request->merge(['slug' => Str::slug($request->input('slug'))]);
        if (blank($request->input('slug')) && is_string($request->input('title'))) $request->merge(['slug' => Str::slug($request->input('title'))]);
        $type = $request->input('type');
        $rules = [
            'type' => ['required', Rule::in(['differences', 'mosaics'])],
            'title' => ['required', 'string', 'min:2', 'max:180'],
            'slug' => ['nullable', 'string', 'max:190', Rule::unique('interactive_games', 'slug')->ignore($game?->id)],
            'description' => ['nullable', 'string', 'max:1500'],
            'image_a' => ['required', 'string', 'max:255', 'regex:/^(https?:\/\/[A-Za-z0-9.-]+(?::[0-9]+)?\/[A-Za-z0-9._\/-]+|\/?[A-Za-z0-9._\/-]+)$/', 'not_regex:/(^|\/)\.\.(\/|$)/'],
            'image_b' => [$type === 'differences' ? 'required' : 'nullable', 'string', 'max:255', 'regex:/^(https?:\/\/[A-Za-z0-9.-]+(?::[0-9]+)?\/[A-Za-z0-9._\/-]+|\/?[A-Za-z0-9._\/-]+)$/', 'not_regex:/(^|\/)\.\.(\/|$)/'],
            'is_published' => ['nullable', 'boolean'],
            'payload_json' => ['required', 'string', 'max:30000'],
        ];
        $data = $request->validate($rules);
        try {
            $payload = json_decode($data['payload_json'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw \Illuminate\Validation\ValidationException::withMessages(['payload_json' => 'Проверьте JSON: формат или кавычки указаны неверно.']);
        }
        if (!is_array($payload) || array_is_list($payload)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['payload_json' => 'Ожидается JSON-объект с настройками игры.']);
        }
        if ($type === 'differences') {
            $validator = validator($payload, ['differences' => ['required', 'array', 'min:1', 'max:20'], 'differences.*.x' => ['required', 'numeric', 'between:0,1'], 'differences.*.y' => ['required', 'numeric', 'between:0,1'], 'differences.*.radius' => ['nullable', 'numeric', 'between:0.01,0.2'], 'differences.*.label' => ['nullable', 'string', 'max:100']]);
        } else {
            $validator = validator($payload, ['rows' => ['required', 'integer', 'between:2,8'], 'columns' => ['required', 'integer', 'between:2,8']]);
        }
        $validator->validate();
        $slug = Str::slug(($data['slug'] ?? '') ?: $data['title']);
        if ($slug === '') $slug = 'game-'.Str::random(8);
        return [
            'type' => $type, 'title' => $data['title'], 'slug' => $slug,
            'description' => $data['description'] ?? null,
            'image_a' => $data['image_a'] ?: ($game?->image_a),
            'image_b' => $type === 'differences' ? ($data['image_b'] ?: ($game?->image_b)) : null,
            'data' => $payload, 'is_published' => $request->boolean('is_published'),
        ];
    }
}
