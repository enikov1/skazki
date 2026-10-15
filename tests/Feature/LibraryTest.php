<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Story;
use App\Models\Article;
use App\Models\ArticleComment;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryTest extends TestCase
{
    use RefreshDatabase;

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
        $user = User::factory()->create();
        $user->roles()->attach(Role::where('slug', 'user')->value('id'));
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $this->actingAs($editor)->get('/admin')->assertOk()->assertSee('Управление сайтом');
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
        $this->post(route('comments.vote', $comment), ['vote' => 'dislike'])->assertRedirect();
        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'likes_count' => 0, 'dislikes_count' => 1]);
    }

    public function test_registration_assigns_only_the_regular_user_role(): void
    {
        $this->seed();
        $this->post(route('register.store'), ['name' => 'Маша', 'email' => 'MASHA@example.com', 'password' => 'A-long-pass-2026', 'password_confirmation' => 'A-long-pass-2026', 'terms' => '1'])->assertRedirect(route('home'));
        $user = User::where('email', 'masha@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
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

    public function test_editors_manage_articles_but_only_administrators_publish_them(): void
    {
        $this->seed();
        $editor = User::factory()->create();
        $editor->roles()->attach(Role::where('slug', 'editor')->value('id'));
        $this->actingAs($editor)->get(route('admin.articles.index'))->assertOk();
        $this->post(route('admin.articles.store'), ['title' => 'Новая статья для родителей', 'category' => 'Чтение', 'summary' => 'Короткое описание материала.', 'body' => 'Текст материала.', 'reading_minutes' => 3])->assertRedirect();
        $article = Article::where('created_by', $editor->id)->firstOrFail();
        $this->patch(route('admin.articles.status', $article), ['status' => 'published'])->assertForbidden();
    }
}
