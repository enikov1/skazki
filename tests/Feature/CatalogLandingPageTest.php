<?php

namespace Tests\Feature;

use App\Models\CatalogLandingPage;
use App\Models\Genre;
use App\Models\Role;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogLandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_genre_author_and_age_pages_are_indexable_and_filter_stories(): void
    {
        $genreStory = Story::create(['title' => 'Лесная сказка', 'slug' => 'forest-tale', 'author' => 'Анна Авторова', 'genre' => 'Приключения', 'summary' => 'История про лес', 'body' => 'Текст', 'age_min' => 4, 'age_max' => 7, 'status' => 'published', 'published_at' => now()->subMinute()]);
        Story::create(['title' => 'Другая сказка', 'slug' => 'other-tale', 'author' => 'Другой автор', 'genre' => 'Басни', 'summary' => 'История', 'body' => 'Текст', 'age_min' => 9, 'age_max' => 12, 'status' => 'published', 'published_at' => now()->subMinute()]);

        $genre = CatalogLandingPage::create(['type' => 'story_genre', 'title' => 'Сказки о приключениях', 'slug' => 'priklyucheniya', 'filter_value' => 'Приключения', 'description' => 'Описание жанра', 'status' => 'published']);
        CatalogLandingPage::create(['type' => 'author', 'title' => 'Сказки Анны Авторовой', 'slug' => 'anna-avtorova', 'filter_value' => 'Анна Авторова', 'status' => 'published']);
        CatalogLandingPage::create(['type' => 'age', 'title' => 'Сказки для детей 4–7 лет', 'slug' => '4-7-let', 'age_min' => 4, 'age_max' => 7, 'status' => 'published']);

        $this->get(route('catalog-pages.story-genre', $genre->slug))->assertOk()->assertSee('Сказки о приключениях')->assertSee('Лесная сказка')->assertDontSee('Другая сказка')->assertSee('index,follow');
        $this->get(route('catalog-pages.author', 'anna-avtorova'))->assertOk()->assertSee('Лесная сказка')->assertDontSee('Другая сказка');
        $this->get(route('catalog-pages.age', '4-7-let'))->assertOk()->assertSee('Лесная сказка')->assertDontSee('Другая сказка');
        $this->get(route('sitemap'))->assertOk()->assertSee(route('catalog-pages.story-genre', $genre->slug), false)->assertSee(route('catalog-pages.author', 'anna-avtorova'), false);
    }

    public function test_admin_can_create_catalog_landing_page_and_description_is_limited_to_two_thousand_characters(): void
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Администратор']);
        $admin->roles()->attach($role);

        $this->actingAs($admin)->get(route('admin.catalog-pages.index'))->assertOk()->assertSee('SEO-страницы каталога');
        $this->actingAs($admin)->postJson(route('admin.catalog-pages.store'), [
            'type' => 'story_genre', 'title' => 'Народные сказки', 'slug' => 'narodnye-skazki', 'filter_value' => 'Народные сказки',
            'description' => str_repeat('а', 2001), 'status' => 'draft',
        ])->assertUnprocessable()->assertJsonValidationErrors('description');

        $this->actingAs($admin)->postJson(route('admin.catalog-pages.store'), [
            'type' => 'story_genre', 'title' => 'Народные сказки', 'slug' => 'narodnye-skazki', 'filter_value' => 'Народные сказки',
            'description' => '[b]Добрые истории[/b]', 'seo_title' => 'Народные сказки детям', 'status' => 'draft',
        ])->assertOk()->assertJsonPath('message', 'SEO-страница каталога создана.');

        $this->assertDatabaseHas('catalog_landing_pages', ['slug' => 'narodnye-skazki', 'status' => 'draft']);
    }

    public function test_admin_can_generate_editable_draft_pages_for_existing_catalog_values(): void
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Администратор']);
        $admin->roles()->attach($role);
        Genre::create(['name' => 'Приключения']);
        Story::create(['title' => 'Авторская сказка', 'slug' => 'authors-story', 'author' => 'Автор Тестов', 'genre' => 'Приключения', 'summary' => 'Кратко', 'body' => 'Текст', 'age_min' => 3, 'age_max' => 8, 'status' => 'draft']);

        $this->actingAs($admin)->postJson(route('admin.catalog-pages.generate'))->assertOk()->assertJsonPath('message', 'Создано черновиков: 11. Заполните описание и SEO-поля, затем опубликуйте страницы.');
        $this->actingAs($admin)->postJson(route('admin.catalog-pages.generate'))->assertOk()->assertJsonPath('message', 'Все страницы для текущих жанров, авторов и возрастов уже созданы.');

        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'story_genre', 'filter_value' => 'Приключения', 'status' => 'draft']);
        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'audio_genre', 'filter_value' => 'Приключения', 'status' => 'draft']);
        $this->assertDatabaseHas('catalog_landing_pages', ['type' => 'author', 'filter_value' => 'Автор Тестов', 'status' => 'draft']);
        $this->assertSame(11, CatalogLandingPage::count());
    }

    public function test_changing_a_published_catalog_slug_keeps_a_permanent_redirect(): void
    {
        $admin = User::factory()->create();
        $role = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Администратор']);
        $admin->roles()->attach($role);
        Story::create(['title' => 'Сказка о море', 'slug' => 'sea-tale', 'genre' => 'Приключения', 'summary' => 'Описание', 'body' => 'Текст', 'status' => 'published', 'published_at' => now()->subMinute()]);
        $page = CatalogLandingPage::create(['type' => 'story_genre', 'title' => 'Приключения', 'slug' => 'priklyucheniya-old', 'filter_value' => 'Приключения', 'status' => 'published']);

        $this->actingAs($admin)->putJson(route('admin.catalog-pages.update', $page), [
            'type' => 'story_genre', 'title' => 'Сказки о приключениях', 'slug' => 'skazki-priklyucheniya', 'filter_value' => 'Приключения', 'status' => 'published',
        ])->assertOk();

        $this->get(route('catalog-pages.story-genre', 'priklyucheniya-old'))
            ->assertRedirect(route('catalog-pages.story-genre', 'skazki-priklyucheniya'), 301);
    }
}
