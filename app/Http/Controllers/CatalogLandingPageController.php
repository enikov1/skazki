<?php

namespace App\Http\Controllers;

use App\Models\CatalogLandingPage;
use App\Models\Story;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class CatalogLandingPageController extends Controller
{
    public function storyGenre(string $slug): View|RedirectResponse { return $this->show('story_genre', $slug); }
    public function audioGenre(string $slug): View|RedirectResponse { return $this->show('audio_genre', $slug); }
    public function author(string $slug): View|RedirectResponse { return $this->show('author', $slug); }
    public function age(string $slug): View|RedirectResponse { return $this->show('age', $slug); }
    public function audioAge(string $slug): View|RedirectResponse { return $this->show('audio_age', $slug); }

    private function show(string $type, string $slug): View|RedirectResponse
    {
        $page = CatalogLandingPage::published()->where('type', $type)->where('slug', $slug)->first();
        if (! $page) {
            $redirectedPage = CatalogLandingPage::published()->where('type', $type)
                ->whereHas('slugRedirects', fn ($query) => $query->where('old_slug', $slug))->first();
            abort_unless($redirectedPage, 404);
            return redirect()->to($redirectedPage->publicUrl(), 301);
        }
        $stories = Story::published()
            ->when($type === 'story_genre', fn ($query) => $query->where('genre', $page->filter_value))
            ->when($type === 'audio_genre', fn ($query) => $query->where('genre', $page->filter_value)->whereNotNull('audio_path'))
            ->when($type === 'author', fn ($query) => $query->where('author', $page->filter_value))
            ->when(in_array($type, ['age', 'audio_age'], true), fn ($query) => $query->where('age_min', '<=', $page->age_max)->where('age_max', '>=', $page->age_min))
            ->when($type === 'audio_age', fn ($query) => $query->whereNotNull('audio_path'))
            ->orderByDesc('published_at')->paginate(18)->withQueryString();

        abort_if($stories->total() === 0, 404);

        return view('catalog-pages.show', compact('page', 'stories'));
    }
}
