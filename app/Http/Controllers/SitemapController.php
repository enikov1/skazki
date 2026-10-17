<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\Article;
use App\Models\ColoringCategory;
use App\Models\ColoringSubmission;
use App\Models\InteractiveGame;
use App\Models\CatalogLandingPage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->stream(function (): void {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            echo '<url><loc>'.htmlspecialchars(route('home'), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc></url>';
            foreach ([route('stories.index'), route('audio.index'), route('games.index'), route('games.differences.index'), route('games.mosaics.index'), route('colorings.index'), route('colorings.gallery'), route('articles.index'), route('privacy-policy')] as $url) {
                echo '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc></url>';
            }
            foreach (Story::published()->orderBy('id')->cursor(['slug', 'updated_at']) as $story) {
                echo '<url><loc>'.htmlspecialchars(route('stories.show', $story->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$story->updated_at->toAtomString().'</lastmod></url>';
            }
            foreach (CatalogLandingPage::published()->orderBy('id')->cursor() as $landingPage) {
                $hasMaterials = match ($landingPage->type) {
                    'story_genre' => Story::published()->where('genre', $landingPage->filter_value)->exists(),
                    'audio_genre' => Story::published()->where('genre', $landingPage->filter_value)->whereNotNull('audio_path')->exists(),
                    'author' => Story::published()->where('author', $landingPage->filter_value)->exists(),
                    'age' => Story::published()->where('age_min', '<=', $landingPage->age_max)->where('age_max', '>=', $landingPage->age_min)->exists(),
                    'audio_age' => Story::published()->whereNotNull('audio_path')->where('age_min', '<=', $landingPage->age_max)->where('age_max', '>=', $landingPage->age_min)->exists(),
                    default => false,
                };
                if ($hasMaterials) echo '<url><loc>'.htmlspecialchars($landingPage->publicUrl(), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$landingPage->updated_at->toAtomString().'</lastmod></url>';
            }
            foreach (Article::published()->orderBy('id')->cursor(['slug', 'updated_at']) as $article) {
                echo '<url><loc>'.htmlspecialchars(route('articles.show', $article->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$article->updated_at->toAtomString().'</lastmod></url>';
            }
            foreach (ColoringCategory::query()->where('is_published', true)->whereHas('pages', fn ($query) => $query->where('is_published', true))->orderBy('id')->cursor(['id', 'slug', 'updated_at']) as $category) {
                echo '<url><loc>'.htmlspecialchars(route('colorings.category', $category->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$category->updated_at->toAtomString().'</lastmod></url>';
            }
            $coloringPages = DB::table('coloring_pages')->join('coloring_categories', 'coloring_categories.id', '=', 'coloring_pages.category_id')
                ->where('coloring_pages.is_published', true)->where('coloring_categories.is_published', true)
                ->orderBy('coloring_pages.id')->select(['coloring_pages.slug as page_slug', 'coloring_categories.slug as category_slug', 'coloring_pages.updated_at']);
            foreach ($coloringPages->cursor() as $page) {
                echo '<url><loc>'.htmlspecialchars(route('colorings.page', [$page->category_slug, $page->page_slug]), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$page->updated_at.'</lastmod></url>';
            }
            foreach (InteractiveGame::published()->orderBy('id')->cursor(['type', 'slug', 'updated_at']) as $game) {
                $route = $game->type === 'differences' ? 'games.differences.show' : 'games.mosaics.show';
                echo '<url><loc>'.htmlspecialchars(route($route, $game->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$game->updated_at->toAtomString().'</lastmod></url>';
            }
            foreach (Story::published()->whereHas('quizQuestions')->orderBy('id')->cursor(['slug', 'updated_at']) as $story) {
                echo '<url><loc>'.htmlspecialchars(route('stories.quiz', $story->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$story->updated_at->toAtomString().'</lastmod></url>';
            }
            foreach (ColoringSubmission::query()->where('status', 'approved')->orderBy('id')->cursor(['slug', 'updated_at']) as $work) {
                echo '<url><loc>'.htmlspecialchars(route('colorings.gallery.work', $work->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$work->updated_at->toAtomString().'</lastmod></url>';
            }
            echo '</urlset>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
