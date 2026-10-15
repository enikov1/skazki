<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Story;
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
}
