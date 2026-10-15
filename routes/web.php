<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\MediaLibraryController;
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
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StoryController::class, 'index'])->name('home');
Route::get('/audio', [StoryController::class, 'audioIndex'])->name('audio.index');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/roditelyam', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/roditelyam/{article:slug}', [ArticleController::class, 'show'])->name('articles.show');
Route::post('/roditelyam/{article:slug}/rating', [ArticleCommentController::class, 'rate'])->middleware(['auth', 'account.active', 'throttle:public-write'])->name('articles.rate');
Route::post('/roditelyam/{article:slug}/comments', [ArticleCommentController::class, 'store'])->middleware(['auth', 'account.active', 'throttle:public-write'])->name('article-comments.store');
Route::post('/article-comments/{comment}/vote', [ArticleCommentController::class, 'vote'])->middleware(['auth', 'account.active', 'throttle:public-write'])->name('article-comments.vote');
Route::get('/skazki/{story:slug}', [StoryController::class, 'show'])->name('stories.show');
Route::get('/skazki/{story:slug}/audio', [StoryController::class, 'audio'])->name('stories.audio');
Route::get('/skazki/{story:slug}/audio/download', [StoryController::class, 'downloadAudio'])->name('stories.audio.download');
Route::post('/skazki/{story:slug}/comments', [CommentController::class, 'store'])->middleware(['auth', 'account.active', 'throttle:public-write'])->name('comments.store');
Route::post('/comments/{comment}/vote', [CommentController::class, 'vote'])->middleware(['auth', 'account.active', 'throttle:public-write'])->name('comments.vote');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:login')->name('register.store');
    Route::get('/forgot-password', [PasswordController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordController::class, 'sendLink'])->middleware('throttle:login')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware('throttle:login')->name('password.update');
    Route::get('/oauth/{provider}', [OAuthController::class, 'redirect'])->whereIn('provider', ['yandex', 'vkontakte'])->name('oauth.redirect');

    // Keep old links working while exposing readable, standard URLs in the interface.
    Route::get('/vhod', fn (\Illuminate\Http\Request $request) => redirect()->route('login', $request->query(), 301));
    Route::post('/vhod', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/registratsiya', fn (\Illuminate\Http\Request $request) => redirect()->route('register', $request->query(), 301));
    Route::post('/registratsiya', [RegisterController::class, 'store'])->middleware('throttle:login');
    Route::get('/zabyl-parol', fn (\Illuminate\Http\Request $request) => redirect()->route('password.request', $request->query(), 301));
    Route::post('/zabyl-parol', [PasswordController::class, 'sendLink'])->middleware('throttle:login');
    Route::get('/sbros-parol/{token}', fn (\Illuminate\Http\Request $request, string $token) => redirect()->route('password.reset', ['token' => $token] + $request->query(), 301));
    Route::post('/sbros-parol', [PasswordController::class, 'reset'])->middleware('throttle:login');
});
Route::get('/oauth/{provider}/callback', [OAuthController::class, 'callback'])->middleware('account.active')->whereIn('provider', ['yandex', 'vkontakte'])->name('oauth.callback');
Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');
    $verifyEmail = function (\Illuminate\Foundation\Auth\EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('home')->with('status', 'Адрес почты подтверждён.');
    };
    Route::get('/email/verify/{id}/{hash}', $verifyEmail)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        if ($request->expectsJson()) return response()->json(['message' => 'Письмо для подтверждения отправлено.']);
        return back()->with('status', 'Письмо для подтверждения отправлено.');
    })->middleware('throttle:6,1')->name('verification.send');

    Route::get('/podtverdit-email', fn (\Illuminate\Http\Request $request) => redirect()->route('verification.notice', $request->query(), 301));
    Route::get('/podtverdit-email/{id}/{hash}', $verifyEmail)->middleware(['signed', 'throttle:6,1']);
    Route::post('/podtverdit-email', function (\Illuminate\Http\Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Письмо для подтверждения отправлено.');
    })->middleware('throttle:6,1');
});
Route::post('/logout', [LoginController::class, 'destroy'])->middleware(['auth', 'account.active'])->name('logout');
Route::post('/vykhod', [LoginController::class, 'destroy'])->middleware(['auth', 'account.active']);
Route::middleware(['auth', 'account.active'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/audio/queue', [StoryController::class, 'saveAudioQueue'])->middleware('throttle:30,1')->name('audio.queue.save');
    Route::post('/skazki/{story:slug}/audio/progress', [StoryController::class, 'saveAudioProgress'])->middleware('throttle:public-write')->name('stories.audio.progress');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:login')->name('profile.password');
    Route::put('/profile/avatar', [ProfileController::class, 'updateAvatar'])->middleware('throttle:public-write')->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.destroy');
    Route::put('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
    Route::put('/profile/newsletter', [ProfileController::class, 'updateNewsletterConsent'])->name('profile.newsletter');
    Route::get('/oauth/{provider}/link', [OAuthController::class, 'link'])->whereIn('provider', ['yandex', 'vkontakte'])->name('oauth.link');
    Route::delete('/profile/social-accounts/{socialAccount}', [ProfileController::class, 'removeSocialAccount'])->name('profile.social-accounts.destroy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'account.active', 'role:admin,moderator,editor'])->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/stories/{story}/preview', [AdminStoryController::class, 'preview'])->middleware('role:admin,editor')->name('stories.preview');
    Route::get('/articles/{article}/preview', [AdminArticleController::class, 'preview'])->middleware('role:admin,editor')->name('articles.preview');
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
    Route::patch('/users/{user}/block', [RoleController::class, 'block'])->middleware('role:admin')->name('users.block');
    Route::patch('/users/{user}/unblock', [RoleController::class, 'unblock'])->middleware('role:admin')->name('users.unblock');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:admin')->name('audit-logs.index');
    Route::get('/media', [MediaLibraryController::class, 'index'])->middleware('role:admin,editor')->name('media.index');
    Route::post('/media', [MediaLibraryController::class, 'store'])->middleware(['role:admin,editor', 'throttle:public-write'])->name('media.store');
    Route::post('/audio', [MediaLibraryController::class, 'storeAudio'])->middleware(['role:admin,editor', 'throttle:public-write'])->name('audio.store');
    Route::get('/audio/{filename}', [MediaLibraryController::class, 'previewAudio'])->where('filename', '[A-Za-z0-9._-]+')->middleware('role:admin,editor')->name('audio.preview');
    Route::get('/genres', [GenreController::class, 'index'])->middleware('role:admin')->name('genres.index');
    Route::post('/genres', [GenreController::class, 'store'])->middleware('role:admin')->name('genres.store');
    Route::patch('/genres/{genre}', [GenreController::class, 'update'])->middleware('role:admin')->name('genres.update');
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->middleware('role:admin')->name('genres.destroy');
});

Route::redirect('/prototype', '/demo/')->name('prototype');
