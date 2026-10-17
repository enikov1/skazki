<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Story;
use App\Models\Genre;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use App\Models\Comment;
use App\Models\ColoringCategory;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['Народные сказки', 'Приключения', 'Басни', 'Тест', 'Проверка'] as $name) {
            Genre::firstOrCreate(['name' => $name]);
        }
    }

    public function test_initial_admin_seed_updates_existing_credentials_and_login_uses_email(): void
    {
        $this->seed();
        $oldPassword = Str::password(24);
        $newPassword = Str::password(24);
        $admin = User::factory()->create(['name' => 'Старое имя', 'email' => 'kosta@example.test', 'password' => Hash::make($oldPassword)]);
        config(['initial-admin.name' => 'kosta683', 'initial-admin.email' => 'KOSTA@example.test', 'initial-admin.password' => $newPassword]);

        $this->seed(DatabaseSeeder::class);

        $admin = $admin->fresh();
        $this->assertSame('kosta683', $admin->name);
        $this->assertTrue(Hash::check($newPassword, $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
        $this->post('/login', ['email' => 'kosta@example.test', 'password' => $newPassword])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_demo_seeder_populates_main_sections_and_is_idempotent(): void
    {
        Storage::fake('local');
        $this->seed(\Database\Seeders\DemoContentSeeder::class);
        $this->seed(\Database\Seeders\DemoContentSeeder::class);

        $this->assertSame(11, Story::count());
        $this->assertSame(9, Story::published()->count());
        $this->assertSame(5, Article::count());
        $this->assertSame(4, ColoringCategory::count());
        $this->assertSame(12, \App\Models\ColoringPage::count());
        $this->assertSame(2, \App\Models\ColoringSubmission::count());
        $this->assertSame(6, \App\Models\StoryQuizQuestion::count());
        $this->assertDatabaseHas('comments', ['status' => 'pending']);
        $this->assertDatabaseHas('article_comments', ['status' => 'pending']);
        $this->assertDatabaseHas('coloring_submissions', ['status' => 'approved']);
        $this->assertDatabaseHas('coloring_submissions', ['status' => 'pending']);
        $this->assertDatabaseHas('users', ['email' => 'demo-admin@example.test']);
        Storage::disk('local')->assertExists('coloring-submissions/demo-approved-fox.png');
    }

    public function test_auth_routes_use_readable_paths_and_redirect_legacy_login_url(): void
    {
        $this->get('/login')->assertOk()->assertSee('data-ajax-form');
        $this->get('/register')->assertOk()->assertSee('data-ajax-form');
        $this->get('/forgot-password')->assertOk()->assertSee('data-ajax-form');
        $this->get('/vhod')->assertRedirect(route('login'), 301);
    }

    public function test_dedicated_story_catalog_lists_all_published_stories_and_filters_results(): void
    {
        Story::create(['title' => 'Лесная сказка', 'slug' => 'lesnaya-skazka', 'genre' => 'Приключения', 'summary' => 'Путешествие по лесу', 'body' => 'Текст', 'age_min' => 4, 'age_max' => 7, 'status' => 'published', 'published_at' => now()->subMinute()]);
        Story::create(['title' => 'Народная история', 'slug' => 'narodnaya-istoriya', 'genre' => 'Народные сказки', 'summary' => 'Добрая история', 'body' => 'Текст', 'age_min' => 3, 'age_max' => 5, 'status' => 'published', 'published_at' => now()->subMinute()]);
        Story::create(['title' => 'Черновик', 'slug' => 'draft-story', 'genre' => 'Тест', 'summary' => 'Не опубликовано', 'body' => 'Текст']);

        $this->get(route('stories.index'))->assertOk()->assertSee('Все сказки')->assertSee('Лесная сказка')->assertSee('Народная история')->assertDontSee('Черновик');
        $filtered = $this->get(route('stories.index', ['genre' => 'Приключения', 'age' => 6]))->assertOk()->assertSee('Лесная сказка')->assertSee('Найдено сказок: 1');
        $this->assertSame(1, substr_count($filtered->getContent(), 'class="book-card"'));
    }

    public function test_story_print_sheet_contains_only_story_body_illustration_and_admin_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('branding/print.png', 'fake-image');
 