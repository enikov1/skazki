<?php
namespace Tests\Feature;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class VisualInteractionTest extends TestCase
{
    use RefreshDatabase;
    public function test_ajax_article_filters_include_only_matching_published_articles_and_render_an_empty_state(): void
    {
        $base = ['category' => 'Практика', 'summary' => 'Чтение вместе', 'body' => 'Текст', 'reading_minutes' => 3, 'status' => 'published', 'published_at' => now()->subMinute()];
        Article::create([...$base, 'title' => 'Вечерняя история', 'slug' => 'evening-story']);
        Article::create([...$base, 'title' => 'Другой раздел', 'slug' => 'other-topic', 'category' => 'Развитие']);
        Article::create([...$base, 'title' => 'Скрытый черновик', 'slug' => 'draft-story', 'status' => 'draft']);
        $response = $this->getJson(route('articles.index', ['category' => 'Практика', 'q' => 'Вечерняя']))->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('Вечерняя история', $response->json('html'));
        $this->assertStringNotContainsString('Другой раздел', $response->json('html'));
        $this->assertStringNotContainsString('Скрытый черновик', $response->json('html'));
        $this->assertStringNotContainsString('<html', $response->json('html'));
        $empty = $this->getJson(route('articles.index', ['q' => 'ничего-не-найдено']))->assertOk()->assertJsonPath('count', 0);
        $this->assertStringContainsString('Подходящих статей пока нет', $empty->json('html'));
    }
}
