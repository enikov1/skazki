<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\RespondsToAjax;
use App\Http\Controllers\Controller;
use App\Models\CatalogLandingPage;
use App\Models\Genre;
use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogLandingPageAdminController extends Controller
{
    use RespondsToAjax;

    public function index(Request $request): View
    {
        $pages = CatalogLandingPage::query()->orderBy('type')->orderBy('title')->paginate(25);
        return view('admin.catalog-pages.index', compact('pages'));
    }

    public function generate(Request $request): RedirectResponse|JsonResponse
    {
        $created = 0;
        foreach (Genre::query()->orderBy('name')->pluck('name') as $genre) {
            foreach (['story_genre' => 'Сказки', 'audio_genre' => 'Аудиосказки'] as $type => $prefix) {
                $created += CatalogLandingPage::firstOrCreate(
                    ['type' => $type, 'filter_value' => $genre],
                    ['title' => $prefix.' жанра «'.$genre.'»', 'slug' => $this->generatedSlug($type, Str::slug($genre)), 'status' => 'draft'],
                )->wasRecentlyCreated ? 1 : 0;
            }
        }
        foreach (Story::query()->whereNotNull('author')->where('author', '<>', '')->distinct()->orderBy('author')->pluck('author') as $author) {
            $created += CatalogLandingPage::firstOrCreate(
                ['type' => 'author', 'filter_value' => $author],
                ['title' => 'Сказки автора '.$author, 'slug' => $this->generatedSlug('author', Str::slug($author)), 'status' => 'draft'],
            )->wasRecentlyCreated ? 1 : 0;
        }
        foreach ([[3, 5], [6, 8], [9, 12], [13, 18]] as [$min, $max]) {
            foreach (['age' => 'Сказки', 'audio_age' => 'Аудиосказки'] as $type => $prefix) {
                $created += CatalogLandingPage::firstOrCreate(
                    ['type' => $type, 'age_min' => $min, 'age_max' => $max],
                    ['title' => $prefix.' для детей '.$min.'–'.$max.' лет', 'slug' => $this->generatedSlug($type, $min.'-'.$max.'-let'), 'status' => 'draft'],
                )->wasRecentlyCreated ? 1 : 0;
            }
        }

        $message = $created ? "Создано черновиков: {$created}. Заполните описание и SEO-поля, затем опубликуйте страницы." : 'Все страницы для текущих жанров, авторов и возрастов уже созданы.';
        return $this->respond($request, $message, route('admin.catalog-pages.index'));
    }

    private function generatedSlug(string $type, string $base): string
    {
        $slug = $base ?: 'page';
        $suffix = 2;
        while (CatalogLandingPage::query()->where('type', $type)->where('slug', $slug)->exists()
            || \App\Models\CatalogLandingRedirect::query()->where('type', $type)->where('old_slug', $slug)->exists()) {
            $slug = ($base ?: 'page').'-'.$suffix++;
        }
        return $slug;
    }

    public function create(): View
    {
        return view('admin.catalog-pages.form', ['page' => new CatalogLandingPage(), ...$this->choices()]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $page = CatalogLandingPage::create($this->validated($request));
        return $this->respond($request, 'SEO-страница каталога создана.', route('admin.catalog-pages.edit', $page));
    }

    public function edit(CatalogLandingPage $catalogPage): View
    {
        return view('admin.catalog-pages.form', ['page' => $catalogPage, ...$this->choices()]);
    }

    private function choices(): array
    {
        return [
            'genres' => Genre::query()->orderBy('name')->pluck('name'),
            'authors' => Story::query()->whereNotNull('author')->where('author', '<>', '')->distinct()->orderBy('author')->pluck('author'),
        ];
    }

    public function update(Request $request, CatalogLandingPage $catalogPage): RedirectResponse|JsonResponse
    {
        $oldSlug = $catalogPage->slug;
        $catalogPage->update($this->validated($request, $catalogPage));
        if ($oldSlug !== $catalogPage->slug) {
            $catalogPage->slugRedirects()->firstOrCreate(['type' => $catalogPage->type, 'old_slug' => $oldSlug]);
            $catalogPage->slugRedirects()->where('old_slug', $catalogPage->slug)->delete();
        }
        return $this->respond($request, 'SEO-страница каталога обновлена.');
    }

    private function validated(Request $request, ?CatalogLandingPage $page = null): array
    {
        if (blank($request->input('slug')) && is_string($request->input('title')) && filled($request->input('title'))) $request->merge(['slug' => Str::slug($request->input('title'))]);

        $data = $request->validate([
            'type' => ['required', Rule::in(CatalogLandingPage::TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:180', Rule::unique('catalog_landing_pages', 'slug')->where(fn ($query) => $query->where('type', $request->input('type')))->ignore($page?->id), Rule::unique('catalog_landing_redirects', 'old_slug')->where('type', $request->input('type'))->ignore($page?->id, 'catalog_landing_page_id')],
            'filter_value' => ['nullable', 'string', 'max:180', Rule::unique('catalog_landing_pages', 'filter_value')->where('type', $request->input('type'))->ignore($page?->id)],
            'age_min' => ['nullable', 'integer', 'between:0,18', Rule::unique('catalog_landing_pages', 'age_min')->where(fn ($query) => $query->where('type', $request->input('type'))->where('age_max', $request->input('age_max')))->ignore($page?->id)],
            'age_max' => ['nullable', 'integer', 'between:0,18', 'gte:age_min'],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover_path' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'seo_description' => ['nullable', 'string', 'max:320'],
            'status' => ['required', Rule::in(['draft', 'published'])],
        ]);

        if ($page && $data['type'] !== $page->type) {
            throw \Illuminate\Validation\ValidationException::withMessages(['type' => 'Тип страницы нельзя изменить после создания, так как он задаёт её постоянный адрес. Создайте новую страницу нужного типа.']);
        }

        if (in_array($data['type'], ['story_genre', 'audio_genre', 'author'], true) && blank($data['filter_value'] ?? null)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['filter_value' => 'Укажите жанр или имя автора для связи страницы с материалами.']);
        }
        if ($data['type'] === 'age' && (! isset($data['age_min'], $data['age_max']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['age_min' => 'Укажите возрастной диапазон.']);
        }
        if ($data['type'] === 'audio_age' && (! isset($data['age_min'], $data['age_max']))) {
            throw \Illuminate\Validation\ValidationException::withMessages(['age_min' => 'Укажите возрастной диапазон.']);
        }

        if ($data['status'] === 'published') {
            $materials = Story::published()
                ->when($data['type'] === 'story_genre', fn ($query) => $query->where('genre', $data['filter_value'] ?? null))
                ->when($data['type'] === 'audio_genre', fn ($query) => $query->where('genre', $data['filter_value'] ?? null)->whereNotNull('audio_path'))
                ->when($data['type'] === 'author', fn ($query) => $query->where('author', $data['filter_value'] ?? null))
                ->when(in_array($data['type'], ['age', 'audio_age'], true), fn ($query) => $query->where('age_min', '<=', $data['age_max'])->where('age_max', '>=', $data['age_min']))
                ->when($data['type'] === 'audio_age', fn ($query) => $query->whereNotNull('audio_path'));
            if (! $materials->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status' => 'Опубликовать страницу можно после появления подходящих опубликованных материалов.']);
            }
        }

        if (in_array($data['type'], ['age', 'audio_age'], true)) {
            $data['filter_value'] = null;
        } else {
            $data['age_min'] = $data['age_max'] = null;
        }
        return $data;
    }
}
