<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Story;
use App\Models\Genre;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use App\Models\Comment;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $admin = User::factory()->create(['name' => 'Старое имя', 'email' => 'kosta@example.test', 'password' => Hash::make('Old-password-2026')]);
        config(['initial-admin.name' => 'kosta683', 'initial-admin.email' => 'KOSTA@example.test', 'initial-admin.password' => 'New-admin-password-2026']);

        $this->seed(DatabaseSeeder::class);

        $admin = $admin->fresh();
        $this->assertSame('kosta683', $admin->name);
        $this->assertTrue(Hash::check('New-admin-password-2026', $admin->password));
        $this->assertTrue($admin->hasRole('admin'));
        $this->post('/login', ['email' => 'kosta@example.test', 'password' => 'New-admin-password-2026'])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_auth_routes_use_readable_paths_and_redirect_legacy_login_url(): void
    {
        $this->get('/login')->assertOk()->assertSee('data-ajax-form');
        $this->get('/register')->assertOk()->assertSee('data-ajax-form');
        $this->get('/forgot-password')->assertOk()->assertSee('data-ajax-form');
        $this->get('/vhod')->assertRedirect(route('login'), 301);
    }

    public function test_bbeditor_renders_only_allowed_formatting_and_escapes_html(): void
    {
        $html = app(\App\Support\BBEditor::class)->render('[h2]Заголовок[/h2][b]Жирный[/b] <script>alert(1)</script>');

        $this->assertStringContainsString('<h2>Заголовок</h2>', $html);
        $this->assertStringContainsString('<ol><li>Первый</li></ol>', app(\App\Support\BBEditor::class)->render('[olist][li]Первый[/li][/olist]'));
        $this->assertStringContainsString('<strong>Жирный</strong>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_editors_can_upload_and_pick_media_but_regular_users_cannot(): void
    {
        $this->seed();
        Storage::fake('public');
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));

        $this->actingAs($editor)->get(route('admin.media.index', ['select' => 'cover_path']))->assertOk()->assertSee('Медиатека');
        $ajaxUpload = $this->postJson(route('admin.media.store', ['select' => 'cover_path']), ['image' => UploadedFile::fake()->image('ajax-cover.png', 400, 300)]);
        $ajaxUpload->assertOk()->assertJsonPath('media.name', basename($ajaxUpload->json('media.path')));
        $this->assertStringStartsWith('storage/media/', $ajaxUpload->json('media.path'));
        $this->post(route('admin.media.store', ['select' => 'cover_path']), ['image' => UploadedFile::fake()->image('illustration.webp', 400, 300)])
            ->assertRedirect(route('admin.media.index', ['select' => 'cover_path']));
        $files = Storage::disk('public')->allFiles('media');
        $this->assertCount(2, $files);
        Storage::disk('public')->assertExists($files[0]);

        auth()->logout();
        $reader = User::factory()->create();
        $reader->roles()->attach(Role::where('slug', 'user')->value('id'));
        $this->actingAs($reader)->get(route('admin.media.index'))->assertForbidden();
    }

    public function test_editors_can_upload_private_mp3_and_audio_is_only_served_for_published_stories(): void
    {
        $this->seed();
        Storage::fake('local');
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));

        $upload = $this->actingAs($editor)->postJson(route('admin.audio.store'), [
            'audio' => UploadedFile::fake()->create('kolobok.mp3', 128, 'audio/mpeg'),
        ]);
        $upload->assertOk()->assertJsonPath('message', 'MP3 загружен в приватное хранилище.');
        $path = $upload->json('audio.path');
        $this->assertStringStartsWith('audio/', $path);
        Storage::disk('local')->assertExists($path);
        $this->assertFileDoesNotExist(public_path($path));
        $this->get($upload->json('audio.url'))->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        $this->get($upload->json('audio.url'), ['Range' => 'bytes=0-3'])
            ->assertStatus(206)->assertHeader('Content-Range', 'bytes 0-3/'.Storage::disk('local')->size($path));

        $draft = Story::create(['title' => 'Черновое аудио', 'slug' => 'draft-audio', 'genre' => 'Тест', 'summary' => 'Описание', 'body' => 'Текст', 'audio_path' => $path]);
        $this->get(route('stories.audio', $draft))->assertNotFound();
        $published = Story::create(['title' => 'Опубликованное аудио', 'slug' => 'published-audio', 'genre' => 'Тест', 'summary' => 'Описание', 'body' => 'Текст', 'audio_path' => $path, 'status' => 'published', 'published_at' => now()->subMinute()]);
        $this->get(route('stories.audio', $published))->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        $this->get(route('stories.audio.download', $published))->assertNotFound();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $this->actingAs($admin);
        $this->putJson(route('admin.stories.update', $published), [
            'title' => $published->title, 'slug' => $published->slug, 'genre' => 'Тест', 'summary' => $published->summary,
            'body' => $published->body, 'age_min' => 3, 'age_max' => 8, 'reading_minutes' => 5,
            'audio_path' => $path, 'allow_download' => '1',
        ])->assertOk();
        $this->assertTrue($published->fresh()->allow_download);
        $this->get(route('stories.audio.download', $published))->assertOk()->assertHeader('Content-Type', 'audio/mpeg')->assertHeader('Content-Disposition');

        $this->postJson(route('admin.audio.store'), ['audio' => UploadedFile::fake()->create('track.txt', 10, 'text/plain')])->assertUnprocessable();
    }

    public function test_audio_catalog_lists_only_published_tracks_and_filters_them_over_ajax(): void
    {
        $available = Story::create([
            'title' => 'Лесная история', 'slug' => 'lesnaya-istoriya', 'author' => 'Иван Автор', 'genre' => 'Приключения',
            'summary' => 'Описание', 'body' => 'Текст', 'age_min' => 4, 'age_max' => 7, 'audio_path' => 'audio/forest.mp3',
            'allow_download' => true, 'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        Story::create([
            'title' => 'Народная история', 'slug' => 'narodnaya-istoriya', 'genre' => 'Народные сказки',
            'summary' => 'Описание', 'body' => 'Текст', 'age_min' => 3, 'age_max' => 5, 'audio_path' => 'audio/folk.mp3',
            'status' => 'published', 'published_at' => now()->subMinute(),
        ]);
        Story::create([
            'title' => 'Черновой трек', 'slug' => 'draft-audio-track', 'genre' => 'Приключения',
            'summary' => 'Описание', 'body' => 'Текст', 'audio_path' => 'audio/draft.mp3',
        ]);
        Story::create([
            'title' => 'История без аудио', 'slug' => 'no-audio-story', 'genre' => 'Приключения',
            'summary' => 'Описание', 'body' => 'Текст', 'status' => 'published', 'published_at' => now()->subMinute(),
        ]);

        $this->get(route('audio.index'))->assertOk()
            ->assertSee('Лесная история')->assertSee('Народная история')
            ->assertSee('data-audio-id="lesnaya-istoriya"', false)
            ->assertSee('data-audio-id="narodnaya-istoriya"', false)
            ->assertDontSee('data-audio-id="draft-audio-track"', false)
            ->assertDontSee('data-audio-id="no-audio-story"', false)
            ->assertSee('data-audio-results', false)->assertSee('id="audioDock"', false)
            ->assertSee('id="dockSleepTimer"', false)->assertSee('До конца сказки');
        $catalogHtml = $this->get(route('audio.index'))->getContent();
        $this->assertStringContainsString(route('stories.audio.download', $available), $catalogHtml);
        $this->assertStringNotContainsString('narodnaya-istoriya/audio/download', $catalogHtml);

        $filtered = $this->getJson(route('audio.index', ['genre' => 'Приключения', 'age' => '5-8', 'q' => 'Лесная']));
        $filtered->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString($available->slug, $filtered->json('html'));
        $this->assertStringNotContainsString('narodnaya-istoriya', $filtered->json('html'));
        $this->getJson(route('audio.index', ['age' => 'not-an-age']))->assertUnprocessable();
    }

    public function test_admin_can_manage_genres_and_story_editor_uses_the_genre_catalog(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $genre = Genre::create(['name' => 'Старое название']);
        $story = Story::create(['title' => 'История с жанром', 'slug' => 'genre-story', 'genre' => $genre->name, 'summary' => 'Описание', 'body' => 'Текст']);

        $this->actingAs($admin)->get(route('admin.genres.index'))->assertOk()->assertSee('Жанры сказок')->assertSee('Старое название');
        $this->get(route('admin.stories.create'))->assertOk()->assertSee('data-custom-select')->assertSee('Народные сказки')->assertSee('name="allow_download"', false)->assertSee('Разрешить скачивание MP3');
        $this->postJson(route('admin.genres.store'), ['name' => 'Сказки о животных'])
            ->assertOk()->assertJsonPath('genre.name', 'Сказки о животных');
        $this->postJson(route('admin.genres.store'), ['name' => 'Сказки о животных'])->assertUnprocessable();
        $this->patchJson(route('admin.genres.update', $genre), ['name' => 'Фольклор'])
            ->assertOk()->assertJsonPath('genre.name', 'Фольклор');
        $this->assertSame('Фольклор', $story->fresh()->genre);
        $this->deleteJson(route('admin.genres.destroy', $genre))->assertUnprocessable();
        $this->postJson(route('admin.stories.store'), ['title' => 'Неизвестный жанр', 'genre' => 'Несуществующий', 'summary' => 'Описание', 'body' => 'Текст', 'age_min' => 3, 'age_max' => 8, 'reading_minutes' => 5])->assertUnprocessable();

        $story->update(['genre' => 'Народные сказки']);
        $this->deleteJson(route('admin.genres.destroy', $genre))->assertOk()->assertJsonPath('message', 'Жанр удалён.');

        auth()->logout();
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $this->actingAs($editor)->get(route('admin.genres.index'))->assertForbidden();
    }

    public function test_editors_can_preview_drafts_and_status_changes_record_authorship(): void
    {
        $this->seed();
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $story = Story::create(['title' => 'Черновая история', 'slug' => 'draft-story', 'genre' => 'Тест', 'summary' => 'Описание черновика', 'body' => 'Полный текст для предпросмотра.', 'status' => 'draft', 'created_by' => $editor->id]);
        $article = Article::create(['title' => 'Черновая статья', 'slug' => 'draft-article', 'category' => 'Тест', 'summary' => 'Описание статьи', 'body' => 'Текст статьи для предпросмотра.', 'reading_minutes' => 3, 'status' => 'draft', 'created_by' => $editor->id]);

        $this->actingAs($editor)->get(route('admin.stories.preview', $story))->assertOk()->assertSee('Предпросмотр')->assertSee('Полный текст для предпросмотра.')->assertSee('noindex,nofollow');
        $this->get(route('stories.show', $story))->assertNotFound();
        $this->get(route('admin.articles.preview', $article))->assertOk()->assertSee('Предпросмотр')->assertSee('Текст статьи для предпросмотра.')->assertSee('noindex,nofollow');
        $this->get(route('articles.show', $article))->assertNotFound();

        auth()->logout();
        $anotherEditor = User::factory()->create();
        $anotherEditor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $this->actingAs($anotherEditor)->get(route('admin.stories.preview', $story))->assertForbidden();
        auth()->logout();
        $this->actingAs($editor);

        $this->patch(route('admin.stories.status', $story), ['status' => 'review'])->assertRedirect();
        $this->assertSame($editor->id, $story->fresh()->updated_by);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $editor->id, 'action' => 'story.status_changed', 'subject_id' => $story->id]);
        $this->patch(route('admin.articles.status', $article), ['status' => 'review'])->assertRedirect();
        $this->assertSame($editor->id, $article->fresh()->updated_by);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $editor->id, 'action' => 'article.status_changed', 'subject_id' => $article->id]);
    }

    public function test_editorial_lists_can_be_searched_filtered_and_content_can_be_scheduled(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $story = Story::create(['title' => 'Запланированная сказка', 'slug' => 'scheduled-story', 'genre' => 'Приключения', 'summary' => 'Описание', 'body' => 'Текст', 'status' => 'draft']);
        Story::create(['title' => 'Другая сказка', 'slug' => 'another-story', 'genre' => 'Басни', 'summary' => 'Описание', 'body' => 'Текст', 'status' => 'review']);
        $article = Article::create(['title' => 'Запланированная статья', 'slug' => 'scheduled-article', 'category' => 'Развитие', 'summary' => 'Описание', 'body' => 'Текст', 'reading_minutes' => 3, 'status' => 'draft']);
        Article::create(['title' => 'Другая статья', 'slug' => 'another-article', 'category' => 'Здоровье', 'summary' => 'Описание', 'body' => 'Текст', 'reading_minutes' => 3, 'status' => 'review']);
        $publishAt = now()->addDay()->startOfMinute();

        $this->actingAs($admin)->get(route('admin.stories.index', ['q' => 'scheduled', 'status' => 'draft', 'genre' => 'Приключения']))
            ->assertOk()->assertSee('Запланированная сказка')->assertDontSee('Другая сказка');
        $this->get(route('admin.articles.index', ['q' => 'scheduled', 'status' => 'draft', 'category' => 'Развитие']))
            ->assertOk()->assertSee('Запланированная статья')->assertDontSee('Другая статья');
        $storyResults = $this->getJson(route('admin.stories.index', ['q' => 'scheduled', 'status' => 'draft', 'genre' => 'Приключения']));
        $storyResults->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('Запланированная сказка', $storyResults->json('html'));
        $articleResults = $this->getJson(route('admin.articles.index', ['q' => 'scheduled', 'status' => 'draft', 'category' => 'Развитие']));
        $articleResults->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('Запланированная статья', $articleResults->json('html'));

        $this->patch(route('admin.stories.status', $story), ['status' => 'published', 'publish_at' => $publishAt->format('Y-m-d\\TH:i')])->assertRedirect();
        $this->patch(route('admin.articles.status', $article), ['status' => 'published', 'publish_at' => $publishAt->format('Y-m-d\\TH:i')])->assertRedirect();
        $this->assertSame($publishAt->format('Y-m-d H:i'), $story->fresh()->published_at->format('Y-m-d H:i'));
        $this->assertSame($publishAt->format('Y-m-d H:i'), $article->fresh()->published_at->format('Y-m-d H:i'));
        $this->get(route('stories.show', $story))->assertNotFound();
        $this->get(route('articles.show', $article))->assertNotFound();
        $this->get(route('admin.stories.index'))->assertSee('Запланировано');
        $this->get(route('admin.articles.index'))->assertSee('Запланировано');
    }

    public function test_admin_editors_and_user_controls_support_ajax(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $story = Story::create(['title' => 'Редактируемая сказка', 'slug' => 'ajax-story', 'genre' => 'Приключения', 'summary' => 'Коротко', 'body' => 'Текст', 'status' => 'draft', 'created_by' => $admin->id]);
        $reader = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.stories.edit', $story))->assertOk()->assertSee('data-ajax-form')->assertSee('data-rich-editor')->assertSee('data-custom-select');
        $this->putJson(route('admin.stories.update', $story), ['title' => 'Изменённая сказка', 'slug' => 'ajax-story', 'genre' => 'Приключения', 'summary' => 'Коротко', 'body' => 'Новый текст', 'age_min' => 3, 'age_max' => 8, 'reading_minutes' => 5, 'allow_download' => '0'])
            ->assertOk()->assertJsonPath('message', 'Изменения сохранены.');
        $this->assertSame('Изменённая сказка', $story->fresh()->title);
        $this->patchJson(route('admin.stories.status', $story), ['status' => 'review'])
            ->assertOk()->assertJsonPath('message', 'Статус сказки обновлён.');

        $usersResponse = $this->getJson(route('admin.users.index', ['q' => $reader->email]));
        $usersResponse->assertOk()->assertJsonPath('count', 1);
        $this->assertStringContainsString('Заблокировать', $usersResponse->json('html'));
        $this->patchJson(route('admin.users.block', $reader), ['block_reason' => 'Нарушение правил общения'])
            ->assertOk()->assertJsonPath('message', 'Аккаунт заблокирован.');
        $this->assertNotNull($reader->fresh()->blocked_at);
        $this->patchJson(route('admin.users.unblock', $reader))->assertOk()->assertJsonPath('message', 'Доступ к аккаунту восстановлен.');
        $this->getJson(route('admin.audit-logs.index', ['action' => 'user.blocked']))->assertOk()->assertJsonPath('count', 1);
    }

    public function test_published_story_is_available_and_draft_is_not(): void
    {
        $this->seed();
        $this->get('/')->assertOk()->assertSee('Колобок');
        Story::create(['title' => 'Черновик', 'genre' => 'Проверка', 'summary' => 'Скрытая запись', 'body' => 'Секретный текст', 'status' => 'draft']);
        $this->get('/skazki/chernovik')->assertNotFound();
    }

    public function test_only_authorized_roles_can_open_management(): void
    {
        $this->seed();
        $this->get('/')->assertDontSee('css/admin.css');
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'user')->value('id'));
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $this->actingAs($editor)->get('/admin')->assertOk()->assertSee('Управление сайтом')->assertSee('css/admin.css');
    }

    public function test_public_story_has_unique_title_and_canonical(): void
    {
        $this->seed();
        $this->get('/skazki/kolobok')->assertOk()->assertSee('Колобок')->assertSee('rel="canonical"', false);
    }

    public function test_comments_wait_for_moderation_and_votes_can_be_changed(): void
    {
        $this->seed();
        $story = Story::where('slug', 'kolobok')->firstOrFail();
        $author = User::factory()->create();
        $author->roles()->attach(Role::where('slug', 'user')->value('id'));
        $this->actingAs($author)->post(route('comments.store', $story), ['body' => 'Добрая история!'])->assertRedirect(route('stories.show', $story));
        $comment = Comment::firstOrFail();
        $this->get(route('stories.show', $story))->assertDontSee('Добрая история!');

        $moderator = User::factory()->create();
        $moderator->roles()->attach(Role::where('slug', 'moderator')->value('id'));
        $this->actingAs($moderator)->patch(route('admin.moderation.update', $comment), ['status' => 'approved'])->assertRedirect();
        $this->get(route('stories.show', $story))->assertSee('Добрая история!');

        $this->actingAs($author)->post(route('comments.vote', $comment), ['vote' => 'like'])->assertRedirect();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'likes_count' => 1]);
        $this->postJson(route('comments.vote', $comment), ['vote' => 'dislike'])->assertOk()->assertJsonPath('message', 'Ваш голос сохранён.');
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'likes_count' => 0, 'dislikes_count' => 1]);
    }

    public function test_registration_assigns_only_the_regular_user_role(): void
    {
        $this->seed();
        $this->post(route('register.store'), ['name' => 'Маша', 'email' => 'MASHA@example.com', 'password' => 'A-long-pass-2026', 'password_confirmation' => 'A-long-pass-2026', 'terms' => '1'])->assertRedirect(route('home'));
        $user = User::where('email', 'masha@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertDatabaseHas('user_consent_events', ['user_id' => $user->id, 'consent_type' => 'terms', 'action' => 'granted']);
        $this->assertDatabaseHas('user_consent_events', ['user_id' => $user->id, 'consent_type' => 'privacy_notice', 'action' => 'granted']);
    }

    public function test_authentication_registration_and_password_recovery_support_ajax(): void
    {
        $this->seed();
        $reader = User::factory()->create(['email' => 'ajax-reader@example.test', 'password' => Hash::make('Reader-password-2026')]);

        $invalidLogin = $this->postJson(route('login.store'), ['email' => 'not-an-email', 'password' => 'wrong']);
        $invalidLogin->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertSame('Укажите корректный адрес электронной почты.', $invalidLogin->json('errors.email.0'));
        $this->postJson(route('login.store'), ['email' => $reader->email, 'password' => 'Reader-password-2026'])
            ->assertOk()->assertJsonPath('redirect', route('home'));
        $this->assertAuthenticatedAs($reader);

        auth()->logout();
        $this->postJson(route('register.store'), ['name' => 'Ajax Reader', 'email' => 'ajax-new@example.test', 'password' => 'A-long-pass-2026', 'password_confirmation' => 'A-long-pass-2026'])
            ->assertUnprocessable()->assertJsonValidationErrors('terms');
        $this->postJson(route('register.store'), ['name' => 'Ajax Reader', 'email' => 'ajax-new@example.test', 'password' => 'A-long-pass-2026', 'password_confirmation' => 'A-long-pass-2026', 'terms' => '1'])
            ->assertOk()->assertJsonPath('redirect', route('home'));

        auth()->logout();
        \Illuminate\Support\Facades\Notification::fake();
        $this->postJson(route('password.email'), ['email' => $reader->email])->assertOk()->assertJsonStructure(['message']);
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($reader);
        $this->postJson(route('password.update'), ['token' => $token, 'email' => $reader->email, 'password' => 'New-Password-2026', 'password_confirmation' => 'New-Password-2026'])
            ->assertOk()->assertJsonPath('redirect', route('login'));
        $this->assertTrue(Hash::check('New-Password-2026', $reader->fresh()->password));
    }

    public function test_parent_articles_are_searchable_and_drafts_remain_private(): void
    {
        $this->seed();
        Article::create(['title' => 'Читаем вместе каждый вечер', 'slug' => 'reading-together', 'category' => 'Семейное чтение', 'summary' => 'Несколько добрых советов для всей семьи.', 'body' => 'Начните с короткой истории и читайте без спешки.', 'reading_minutes' => 4, 'status' => 'published', 'published_at' => now()]);
        Article::create(['title' => 'Скрытая статья', 'slug' => 'hidden-article', 'category' => 'Тест', 'summary' => 'Не должна попасть в публичный каталог.', 'body' => 'Черновой текст.', 'status' => 'draft']);

        $this->get(route('articles.index', ['q' => 'вечер']))->assertOk()->assertSee('Читаем вместе каждый вечер')->assertDontSee('Скрытая статья');
        $this->get(route('articles.show', 'reading-together'))->assertOk()->assertSee('Начните с короткой истории')->assertSee('application/ld+json', false);
        $this->get(route('articles.show', 'hidden-article'))->assertNotFound();
        $sitemap = $this->get(route('sitemap'));
        $sitemap->assertOk();
        ob_start();
        $sitemap->baseResponse->sendContent();
        $xml = ob_get_clean();
        $this->assertStringContainsString('/roditelyam/reading-together', $xml);
        $this->assertStringNotContainsString('hidden-article', $xml);
    }

    public function test_article_comments_require_moderation_and_votes_can_change(): void
    {
        $this->seed();
        $article = Article::create(['title' => 'Совет о чтении', 'slug' => 'reading-advice', 'category' => 'Книги', 'summary' => 'Короткое вступление.', 'body' => 'Текст статьи.', 'status' => 'published', 'published_at' => now()]);
        $reader = User::factory()->create();
        $reader->roles()->attach(Role::where('slug', 'user')->value('id'));

        $this->actingAs($reader)->post(route('article-comments.store', $article), ['body' => 'Нам очень понравился этот совет!'])->assertRedirect(route('articles.show', $article));
        $comment = ArticleComment::firstOrFail();
        $this->get(route('articles.show', $article))->assertDontSee('Нам очень понравился этот совет!');

        $moderator = User::factory()->create();
        $moderator->roles()->attach(Role::where('slug', 'moderator')->value('id'));
        $this->actingAs($moderator)->patch(route('admin.articles.comments.moderate', $comment), ['status' => 'approved'])->assertRedirect();
        $this->get(route('articles.show', $article))->assertSee('Нам очень понравился этот совет!');
        $this->actingAs($reader)->post(route('article-comments.vote', $comment), ['vote' => 'like'])->assertRedirect();
        $this->assertDatabaseHas('article_comments', ['id' => $comment->id, 'likes_count' => 1]);
        $this->post(route('article-comments.vote', $comment), ['vote' => 'dislike'])->assertRedirect();
        $this->assertDatabaseHas('article_comments', ['id' => $comment->id, 'likes_count' => 0, 'dislikes_count' => 1]);
    }

    public function test_article_rating_is_editable_and_one_vote_per_reader_is_kept(): void
    {
        $this->seed();
        $article = Article::create(['title' => 'История о семейном чтении', 'slug' => 'family-reading', 'category' => 'Чтение', 'summary' => 'Описание.', 'body' => 'Текст статьи.', 'status' => 'published', 'published_at' => now()]);
        $reader = User::factory()->create();
        $this->actingAs($reader)->post(route('articles.rate', $article), ['rating' => 5])->assertRedirect();
        $this->post(route('articles.rate', $article), ['rating' => 4])->assertRedirect();
        $this->assertDatabaseCount('article_ratings', 1);
        $this->assertDatabaseHas('article_ratings', ['article_id' => $article->id, 'user_id' => $reader->id, 'rating' => 4]);
        $this->get(route('articles.show', $article))->assertOk()->assertSee('4,0')->assertSee('Изменить оценку');
    }

    public function test_editors_manage_articles_but_only_administrators_publish_them(): void
    {
        $this->seed();
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $this->actingAs($editor)->get(route('admin.articles.index'))->assertOk();
        $this->post(route('admin.articles.store'), ['title' => 'Новая статья для родителей', 'category' => 'Чтение', 'summary' => 'Короткое описание материала.', 'body' => 'Текст материала.', 'reading_minutes' => 3])->assertRedirect();
        $article = Article::where('created_by', $editor->id)->firstOrFail();
        $this->patch(route('admin.articles.status', $article), ['status' => 'published'])->assertForbidden();

        $otherEditor = User::factory()->create();
        $otherEditor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $otherArticle = Article::create(['title' => 'Статья другого автора', 'slug' => 'other-editor-article', 'category' => 'Чтение', 'summary' => 'Описание статьи другого редактора.', 'body' => 'Содержание статьи другого редактора.', 'status' => 'draft', 'created_by' => $otherEditor->id]);
        $this->get(route('admin.articles.edit', $otherArticle))->assertForbidden();
        $this->patch(route('admin.articles.status', $otherArticle), ['status' => 'review'])->assertForbidden();

        $otherStory = Story::create(['title' => 'Сказка другого редактора', 'slug' => 'other-editor-story', 'genre' => 'Проверка', 'summary' => 'Описание сказки.', 'body' => 'Содержание сказки.', 'status' => 'draft', 'created_by' => $otherEditor->id]);
        $this->get(route('admin.stories.edit', $otherStory))->assertForbidden();
        $this->patch(route('admin.stories.status', $otherStory), ['status' => 'review'])->assertForbidden();
    }

    public function test_only_users_with_management_permission_can_open_staff_actions(): void
    {
        $this->seed();
        $reader = User::factory()->create();
        $reader->roles()->attach(Role::where('slug', 'user')->value('id'));
        $subject = User::factory()->create();

        $this->actingAs($reader)->get(route('admin.users.index'))->assertForbidden();
        $this->patch(route('admin.users.role', $subject), ['role' => 'editor'])->assertForbidden();
        $this->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_profile_edits_name_changes_password_and_lists_reading_history(): void
    {
        $this->seed();
        $reader = User::factory()->create();
        $story = Story::where('slug', 'kolobok')->firstOrFail();
        $this->actingAs($reader)->get(route('stories.show', $story))->assertOk();
        $this->assertDatabaseHas('reading_histories', ['user_id' => $reader->id, 'story_id' => $story->id]);
        $this->get(route('profile.show'))->assertOk()->assertSee('История сказок')->assertSee($story->title);

        $this->put(route('profile.update'), ['name' => 'Костя'])->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $reader->id, 'name' => 'Костя']);
        $this->putJson(route('profile.update'), ['name' => 'Ajax Костя'])->assertOk()->assertJsonPath('message', 'Имя в профиле обновлено.');
        $this->assertDatabaseHas('users', ['id' => $reader->id, 'name' => 'Ajax Костя']);
        $this->put(route('profile.password'), ['current_password' => 'password-for-tests', 'password' => 'New-Reading-Pass-2026', 'password_confirmation' => 'New-Reading-Pass-2026'])->assertRedirect();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('New-Reading-Pass-2026', $reader->fresh()->password));
    }

    public function test_profile_avatar_preferences_and_optional_consent_are_manageable(): void
    {
        $this->seed();
        Storage::fake('public');
        $reader = User::factory()->create();
        $this->actingAs($reader)->put(route('profile.avatar.update'), ['avatar' => UploadedFile::fake()->image('portrait.jpg', 400, 400)])->assertRedirect();
        $avatarPath = $reader->fresh()->avatar_path;
        $this->assertNotEmpty($avatarPath);
        Storage::disk('public')->assertExists($avatarPath);

        $this->put(route('profile.preferences'), ['reader_font_size' => 24, 'reader_theme' => 'green', 'reader_width' => 'narrow'])->assertRedirect();
        $this->get(route('stories.show', 'kolobok'))->assertOk()->assertSee('reader-theme-green')->assertSee('font-size: 24px; --reader-max-width: 560px', false);

        $this->put(route('profile.newsletter'), ['newsletter' => '1'])->assertRedirect();
        $this->put(route('profile.newsletter'), ['newsletter' => '0'])->assertRedirect();
        $this->assertDatabaseCount('user_consent_events', 2);
        $this->assertDatabaseHas('user_consent_events', ['user_id' => $reader->id, 'consent_type' => 'newsletter', 'action' => 'revoked']);

        $this->delete(route('profile.avatar.destroy'))->assertRedirect();
        $this->assertNull($reader->fresh()->avatar_path);
        Storage::disk('public')->assertMissing($avatarPath);
    }

    public function test_oauth_account_can_only_be_linked_from_the_signed_in_profile(): void
    {
        $this->seed();
        config(['services.yandex.client_id' => 'test-client', 'services.yandex.client_secret' => 'test-secret']);
        $reader = User::factory()->create();
        $external = \Mockery::mock();
        $external->shouldReceive('getId')->andReturn('yandex-user-123');
        $external->shouldReceive('getEmail')->andReturn('reader@example.test');
        $external->shouldReceive('getName')->andReturn('Reader');
        $driver = \Mockery::mock();
        $driver->shouldReceive('redirect')->once()->andReturn(redirect('https://identity.example.test/authorize'));
        $driver->shouldReceive('user')->once()->andReturn($external);
        Socialite::shouldReceive('driver')->with('yandex')->twice()->andReturn($driver);

        $this->actingAs($reader)->get(route('oauth.link', 'yandex'))->assertRedirect('https://identity.example.test/authorize');
        $this->get(route('oauth.callback', 'yandex'))->assertRedirect(route('profile.show'))->assertSessionHas('status', 'Аккаунт привязан. Теперь можно входить через эту сеть.');
        $this->assertDatabaseHas('social_accounts', ['user_id' => $reader->id, 'provider' => 'yandex', 'provider_user_id' => 'yandex-user-123']);
    }

    public function test_oauth_does_not_merge_accounts_by_matching_email_and_prevents_lockout(): void
    {
        $this->seed();
        config(['services.yandex.client_id' => 'test-client', 'services.yandex.client_secret' => 'test-secret']);
        $reader = User::factory()->create(['email' => 'reader@example.test']);
        $external = \Mockery::mock();
        $external->shouldReceive('getId')->andReturn('yandex-user-456');
        $external->shouldReceive('getEmail')->andReturn('reader@example.test');
        $external->shouldReceive('getName')->andReturn('Reader');
        $driver = \Mockery::mock();
        $driver->shouldReceive('redirect')->once()->andReturn(redirect('https://identity.example.test/authorize'));
        $driver->shouldReceive('user')->once()->andReturn($external);
        Socialite::shouldReceive('driver')->with('yandex')->twice()->andReturn($driver);

        $this->get(route('oauth.redirect', 'yandex'))->assertRedirect('https://identity.example.test/authorize');
        $this->get(route('oauth.callback', 'yandex'))->assertRedirect(route('login'))->assertSessionHasErrors('oauth');
        $this->assertDatabaseCount('social_accounts', 0);

        $account = $reader->socialAccounts()->create(['provider' => 'yandex', 'provider_user_id' => 'yandex-user-789']);
        $reader->forceFill(['password_login_enabled' => false])->save();
        $this->actingAs($reader)->delete(route('profile.social-accounts.destroy', $account))->assertSessionHasErrors('oauth');
        $this->assertDatabaseHas('social_accounts', ['id' => $account->id]);
        $this->put(route('profile.password'), ['password' => 'New-Reader-Password-2026', 'password_confirmation' => 'New-Reader-Password-2026'])->assertRedirect();
        $this->assertTrue($reader->fresh()->password_login_enabled);
        $this->delete(route('profile.social-accounts.destroy', $account))->assertRedirect();
        $this->assertDatabaseMissing('social_accounts', ['id' => $account->id]);
    }

    public function test_administrator_can_search_block_restore_and_review_account_audit(): void
    {
        $this->seed();
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $reader = User::factory()->create(['name' => 'Тестовый читатель']);

        $this->actingAs($admin)->get(route('admin.users.index', ['q' => 'Тестовый']))->assertOk()->assertSee($reader->email)->assertSee('Заблокировать');
        $this->patch(route('admin.users.block', $reader), ['block_reason' => 'Нарушение правил общения'])->assertRedirect()->assertSessionHas('status');
        $this->assertNotNull($reader->fresh()->blocked_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.blocked', 'subject_id' => $reader->id]);
        $this->get(route('admin.audit-logs.index', ['action' => 'user.blocked']))->assertOk()->assertSee('Нарушение правил общения');

        $this->patch(route('admin.users.unblock', $reader))->assertRedirect()->assertSessionHas('status');
        $this->assertNull($reader->fresh()->blocked_at);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.unblocked', 'subject_id' => $reader->id]);
    }

    public function test_blocking_self_is_prevented_and_blocked_login_is_denied(): void
    {
        $this->seed();
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $this->actingAs($admin)->patch(route('admin.users.block', $admin), ['block_reason' => 'Проверка блокировки'])->assertSessionHasErrors('block_reason');
        $this->assertNull($admin->fresh()->blocked_at);

        auth()->logout();
        $reader = User::factory()->create(['password' => Hash::make('Reader-password-2026')]);
        $reader->forceFill(['blocked_at' => now(), 'block_reason' => 'Нарушение правил'])->save();
        $this->post(route('login.store'), ['email' => $reader->email, 'password' => 'Reader-password-2026'])->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_blocked_account_session_is_ended_on_authenticated_requests(): void
    {
        $this->seed();
        $reader = User::factory()->create();
        $reader->forceFill(['blocked_at' => now(), 'block_reason' => 'Проверка'])->save();
        $this->actingAs($reader)->get(route('profile.show'))->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_blocking_another_administrator_keeps_one_admin_and_revokes_existing_session(): void
    {
        $this->seed();
        $operator = User::factory()->create();
        $operator->roles()->attach(Role::where('slug', 'admin')->value('id'));
        $target = User::factory()->create();
        $target->roles()->attach(Role::where('slug', 'admin')->value('id'));

        $this->actingAs($operator)->patch(route('admin.users.block', $target), ['block_reason' => 'Учётная запись проверяется'])->assertRedirect();
        $this->assertTrue($target->fresh()->isBlocked());
        $this->assertSame(1, Role::where('slug', 'admin')->firstOrFail()->users()->whereNull('blocked_at')->count());

        $this->actingAs($target->fresh())->get(route('profile.show'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_blocked_oauth_account_cannot_start_a_session(): void
    {
        $this->seed();
        config(['services.yandex.client_id' => 'test-client', 'services.yandex.client_secret' => 'test-secret']);
        $reader = User::factory()->create();
        $reader->forceFill(['blocked_at' => now()])->save();
        $reader->socialAccounts()->create(['provider' => 'yandex', 'provider_user_id' => 'blocked-yandex-id']);
        $external = \Mockery::mock();
        $external->shouldReceive('getId')->andReturn('blocked-yandex-id');
        $driver = \Mockery::mock();
        $driver->shouldReceive('user')->once()->andReturn($external);
        Socialite::shouldReceive('driver')->with('yandex')->once()->andReturn($driver);

        $this->get(route('oauth.callback', 'yandex'))->assertRedirect(route('login'))->assertSessionHasErrors('oauth');
        $this->assertGuest();
    }

}
