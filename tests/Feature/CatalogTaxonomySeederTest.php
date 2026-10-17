<?php

namespace Tests\Feature;

use App\Models\CatalogLandingPage;
use App\Models\ColoringCategory;
use App\Models\Genre;
use App\Models\Story;
use App\Models\User;
use Database\Seeders\CatalogTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTaxonomySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_is_repeatable_and_preserves_editor_changes_without_creating_accounts(): void
    {
        $this->seed(CatalogTaxonomySeeder::class);
        $counts = [Genre::count(), CatalogLandingPage::count(), ColoringCategory::count()];
        $this->assertSame([30, 118, 50], $counts);
        $page = CatalogLandingPage::where('type', 'author')->firstOrFail();
        $page->update(['description' => 'Описание редактора', 'slug' => 'editor-url']);
        $category = ColoringCategory::firstOrFail();
        $category->update(['description' => 'Редакторская категория', 'slug' => 'my-category']);
        $this->seed(CatalogTaxonomySeeder::class);
        $this->assertSame($counts, [Genre::count(), CatalogLandingPage::count(), ColoringCategory::count()]);
        $this->assertSame('Описание редактора', $page->fresh()->description);
        $this->assertSame('editor-url', $page->fresh()->slug);
        $this->assertSame('Редакторская категория', $category->fresh()->description);
        $this->assertSame('my-category', $category->fresh()->slug);
        $this->assertSame(0, User::count());
        $this->assertSame(0, CatalogLandingPage::published()->count());
        $this->assertSame(0, ColoringCategory::where('is_published', true)->count());
    }

    public function test_only_collections_with_matching_material_are_published_and_old_urls_are_reserved(): void
    {
        Story::create(['title' => 'Лесная дружба', 'slug' => 'forest-friendship', 'genre' => 'Животные',
            'author' => 'Александр Пушкин', 'summary' => 'Описание', 'body' => 'Тестовый текст',
            'age_min' => 4, 'age_max' => 5, 'status' => 'published', 'published_at' => now()->subMinute()]);
        $reserved = CatalogLandingPage::create(['type' => 'story_genre', 'filter_value' => 'Другой жанр', 'title' => 'Другая подборка', 'slug' => 'other', 'status' => 'draft']);
        $reserved->slugRedirects()->create(['type' => 'story_genre', 'old_slug' => 'zhivotnye']);
        $this->seed(CatalogTaxonomySeeder::class);
        $genre = CatalogLandingPage::where('type', 'story_genre')->where('filter_value', 'Животные')->firstOrFail();
        $this->assertSame('zhivotnye-2', $genre->slug);
        $this->assertSame('published', $genre->status);
        $this->get($genre->publicUrl())->assertOk()->assertSee('Лесная дружба');
        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'audio_genre', 'filter_value' => 'Животные', 'status' => 'draft']);
        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'author', 'filter_value' => 'Александр Пушкин', 'status' => 'published']);
        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'age', 'age_min' => 4, 'age_max' => 5, 'status' => 'published']);
        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'audio_age', 'age_min' => 4, 'age_max' => 5, 'status' => 'draft']);
    }
}
