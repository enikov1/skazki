<?php

namespace App\Http\Controllers;

use App\Models\Story;
use App\Models\Article;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SitemapController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->stream(function (): void {
            echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
            echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            echo '<url><loc>'.htmlspecialchars(route('home'), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc></url>';
            foreach (Story::published()->orderBy('id')->cursor(['slug', 'updated_at']) as $story) {
                echo '<url><loc>'.htmlspecialchars(route('stories.show', $story->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$story->updated_at->toAtomString().'</lastmod></url>';
            }
            foreach (Article::published()->orderBy('id')->cursor(['slug', 'updated_at']) as $article) {
                echo '<url><loc>'.htmlspecialchars(route('articles.show', $article->slug), ENT_XML1 | ENT_COMPAT, 'UTF-8').'</loc><lastmod>'.$article->updated_at->toAtomString().'</lastmod></url>';
            }
            echo '</urlset>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
