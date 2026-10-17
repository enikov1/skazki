<?php

namespace Tests\Feature;

use App\Models\CatalogLandingPage;
use App\Models\ColoringCategory;
use App\Models\ColoringSubmission;
use App\Models\Role;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionAuditTest extends TestCase
{
    use RefreshDatabase;

    private function story(array $attributes = []): Story
    {
        return Story::create(array_merge(['title' => 'Проверенная сказка', 'slug' => 'audit-story', 'genre' => 'Тест', 'summary' => 'Описание', 'body' => 'Текст', 'age_min' => 3, 'age_max' => 8, 'status' => 'published', 'published_at' => now()->subMinute()], $attributes));
    }

    public function test_bookshelf_is_private_idempotent_and_hides_unpublished_stories(): void
    {
        $story = $this->story();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->postJson(route('stories.favorite', $story), ['saved' => true])->assertUnauthorized();
        $this->actingAs($owner)->postJson(route('stories.favorite', $story), ['saved' => true])->assertOk()->assertJsonPath('saved', true);
        $this->postJson(route('stories.favorite', $story), ['saved' => true])->assertOk();
        $this->assertDatabaseCount('story_favorites', 1);
        $this->get(route('profile.history'))->assertOk()->assertViewHas('storyFavorites', fn ($items) => $items->count() === 1);
        $this->actingAs($other)->get(route('profile.history'))->assertOk()->assertViewHas('storyFavorites', fn ($items) => $items->isEmpty());
        $story->update(['status' => 'draft']);
        $this->actingAs($owner)->get(route('profile.history'))->assertOk()->assertViewHas('storyFavorites', fn ($items) => $items->isEmpty());
        $this->postJson(route('stories.favorite', $story), ['saved' => true])->assertNotFound();
    }

    public function test_export_works_with_real_coloring_favorites_and_progress(): void
    {
        $user = User::factory()->create();
        $category = ColoringCategory::create(['name' => 'Категория', 'slug' => 'audit-category', 'is_published' => true]);
        $page = $category->pages()->create(['title' => 'Рисунок для экспорта', 'slug' => 'export-drawing', 'outline_path' => 'assets/test.png', 'is_published' => true]);
        $user->coloringFavorites()->create(['coloring_page_id' => $page->id]);
        $user->coloringProgress()->create(['coloring_page_id' => $page->id, 'strokes' => []]);
        $response = $this->actingAs($user)->get(route('profile.data-export'))->assertOk();
        $export = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Рисунок для экспорта', $export['coloring_favorites'][0]['page']['title']);
        $this->assertSame('Категория', $export['coloring_progress'][0]['page']['category']);
    }

    public function test_quiz_rejects_sparse_answers_and_guest_results_are_session_private(): void
    {
        $story = $this->story();
        $this->withCredentials()->withCookie(config('session.cookie'), session()->getId());
        $story->quizQuestions()->create(['position' => 0, 'prompt' => 'Вопрос?', 'options' => ['А', 'Б', 'В', 'Г'], 'correct_option' => 0]);
        $this->postJson(route('stories.quiz.submit', $story), ['answers' => [7 => 0]])->assertUnprocessable();
        $this->assertDatabaseCount('story_quiz_attempts', 0);
        $response = $this->postJson(route('stories.quiz.submit', $story), ['answers' => [0]])->assertOk();
        $url = $response->json('redirect');
        $this->get($url)->assertOk()->assertSee('Ваш результат: 1 из 1');
        $this->withSession(['quiz_attempt_ids' => []])->get($url)->assertNotFound();
    }

    public function test_private_image_delivery_rejects_parent_directory_segments(): void
    {
        $user = User::factory()->create();
        $category = ColoringCategory::create(['name' => 'Категория', 'slug' => 'private-images', 'is_published' => true]);
        $page = $category->pages()->create(['title' => 'Рисунок', 'slug' => 'private-image', 'outline_path' => 'assets/test.png', 'is_published' => true]);
        $work = $user->coloringSubmissions()->create(['coloring_page_id' => $page->id, 'title' => 'Работа', 'slug' => 'audit-work', 'file_path' => 'coloring-submissions/../secret.png', 'status' => 'approved', 'guardian_consent_at' => now()]);
        $this->get(route('colorings.gallery.image', $work))->assertNotFound();
    }

    public function test_preview_is_not_indexable_and_authenticated_pages_are_not_cached(): void
    {
        config(['site.indexable' => false]);
        $this->get(route('home'))->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')->assertSee('Перейти к содержимому');
        $this->get(route('robots'))->assertOk()->assertSee('Disallow: /');
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('profile.settings'))->assertOk();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_duplicate_catalog_filters_return_validation_errors_and_redirects_are_type_scoped(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'Администратор', 'slug' => 'admin']));
        $story = $this->story(['audio_path' => 'audio/test.mp3']);
        foreach (['story_genre', 'audio_genre'] as $type) {
            $page = CatalogLandingPage::create(['type' => $type, 'title' => 'Жанр', 'slug' => 'old-genre', 'filter_value' => 'Тест', 'status' => 'published']);
            $this->actingAs($admin)->putJson(route('admin.catalog-pages.update', $page), ['type' => $type, 'title' => 'Жанр', 'slug' => 'new-genre', 'filter_value' => 'Тест', 'status' => 'published'])->assertOk();
        }
        $this->assertDatabaseCount('catalog_landing_redirects', 2);
        $this->postJson(route('admin.catalog-pages.store'), ['type' => 'story_genre', 'title' => 'Дубль', 'slug' => 'duplicate-genre', 'filter_value' => 'Тест', 'status' => 'draft'])->assertUnprocessable()->assertJsonValidationErrors('filter_value');
    }

    public function test_malformed_input_types_are_validation_errors_instead_of_server_errors(): void
    {
        $this->postJson(route('login.store'), ['email' => ['unexpected'], 'password' => 'test'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson(route('register.store'), ['email' => ['unexpected'], 'name' => 'Имя', 'password' => 'test'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $story = $this->story();
        $this->postJson(route('comments.store', $story), ['guest_name' => ['unexpected'], 'body' => 'Проверка типа имени'])->assertUnprocessable()->assertJsonValidationErrors('guest_name');
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::create(['name' => 'Администратор', 'slug' => 'admin']));
        $this->actingAs($admin)->postJson(route('admin.catalog-pages.store'), ['title' => ['unexpected'], 'type' => 'author', 'status' => 'draft'])->assertUnprocessable()->assertJsonValidationErrors('title');
    }
}
