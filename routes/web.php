<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StoryController as AdminStoryController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ArticleCommentController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ArticleController as AdminArticleController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoryController::class, 'index'])->name('home');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/roditelyam', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/roditelyam/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/roditelyam/{article:slug}/rating', [ArticleCommentController::class, 'rate'])->middleware(['auth', 'throttle:public-write'])->name('articles.rate');
Route::post('/roditelyam/{article:slug}/comments', [ArticleCommentController::class, 'store'])->middleware(['auth', 'throttle:public-write'])->name('article-comments.store');
Route::post('/article-comments/{comment}/vote', [ArticleCommentController::class, 'vote'])->middleware(['auth', 'throttle:public-write'])->name('article-comments.vote');
Route::get('/skazki/{story:slug}', [StoryController::class, 'show'])->name('stories.show');
Route::post('/skazki/{story:slug}/comments', [CommentController::class, 'store'])->middleware(['auth', 'throttle:public-write'])->name('comments.store');
Route::post('/comments/{comment}/vote', [CommentController::class, 'vote'])->middleware(['auth', 'throttle:public-write'])->name('comments.vote');

Route::middleware('guest')->group(function (): void {
    Route::get('/vhod', [LoginController::class, 'create'])->name('login');
    Route::post('/vhod', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/registratsiya', [RegisterController::class, 'create'])->name('register');
    Route::post('/registratsiya', [RegisterController::class, 'store'])->middleware('throttle:login')->name('register.store');
    Route::get('/zabyl-parol', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/zabyl-parol', [PasswordController::class, 'sendLink'])->middleware('throttle:login')->name('password.email');
    Route::get('/sbros-parol/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/sbros-parol', [PasswordController::class, 'reset'])->middleware('throttle:login')->name('password.update');
    Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->whereIn('provider', ['yandex', 'vkontakte'])->name('oauth.redirect');
    Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->whereIn('provider', ['yandex', 'vkontakte'])->name('oauth.callback');
});
Route::middleware('auth')->group(function (): void {
    Route::get('/podtverdit-email', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/podtverdit-email/{id}/{hash}', function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('home')->with('status', 'Адрес почты подтверждён.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/podtverdit-email', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Письмо для подтверждения отправлено.');
    })->middleware('throttle:6,1')->name('verification.send');
});
Route::post('/vykhod', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:login')->name('profile.password');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,moderator,editor'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('stories', AdminStoryController::class)->except(['show', 'destroy']);
    Route::resource('articles', AdminArticleController::class)->except(['show', 'destroy']);
    Route::patch('/articles/{article}/status', [AdminArticleController::class, 'status'])->name('articles.status');
    Route::get('/article-comments', [AdminArticleController::class, 'comments'])->middleware('role:admin,moderator')->name('articles.comments');
    Route::patch('/article-comments/{comment}', [AdminArticleController::class, 'moderateComment'])->middleware('role:admin,moderator')->name('articles.comments.moderate');
    Route::patch('/stories/{story}/status', [AdminStoryController::class, 'status'])->name('stories.status');
    Route::get('/moderation', [ModerationController::class, 'index'])->middleware('role:admin,moderator')->name('moderation.index');
    Route::patch('/moderation/{comment}', [ModerationController::class, 'update'])->middleware('role:admin,moderator')->name('moderation.update');
    Route::get('/users', [RoleController::class, 'index'])->middleware('role:admin')->name('users.index');
    Route::patch('/users/{user}/role', [RoleController::class, 'update'])->middleware('role:admin')->name('users.role');
});

Route::redirect('/prototype', '/demo/')->name('prototype');
