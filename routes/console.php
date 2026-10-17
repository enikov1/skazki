<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:skazkomir', function () {
    $this->info('СказкоМир — Laravel site');
})->purpose('Проверить, что консоль приложения доступна');

Artisan::command('site:check', function () {
    $failed = 0;
    $check = function (string $name, bool $ok) use (&$failed): void {
        if ($ok) $this->info('OK: '.$name);
        else { $failed++; $this->error('FAIL: '.$name); }
    };
    $check('APP_ENV=production', app()->environment('production'));
    $check('APP_DEBUG=false', ! config('app.debug'));
    $check('HTTPS APP_URL', str_starts_with((string) config('app.url'), 'https://'));
    $check('APP_KEY задан', filled(config('app.key')));
    $check('Демо-наполнение отключено', ! filter_var(env('SEED_DEMO_CONTENT', false), FILTER_VALIDATE_BOOLEAN));
    $check('Временный ADMIN_PASSWORD удалён', blank(config('initial-admin.password')));
    $check('Настроена отправка почты', ! in_array(config('mail.default'), ['log', 'array'], true));
    $check('Настроена очередь', ! in_array(config('queue.default'), ['sync', 'null'], true));
    $check('Secure session cookie', (bool) config('session.secure'));
    $check('Ссылка на публичную медиатеку', is_dir(public_path('storage')));
    $check('Каталог загрузок доступен для записи', is_writable(storage_path('app/public')));
    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $check('Подключение к БД', true);
        $check('Есть активный администратор', \App\Models\User::query()->whereNull('blocked_at')->whereHas('roles', fn ($query) => $query->where('slug', 'admin'))->exists());
    } catch (\Throwable) {
        $check('Подключение к БД и миграции', false);
    }
    $this->line('Отдельно проверьте постоянное хранилище, восстановление резервной копии, SMTP и работу queue worker.');
    return $failed ? 1 : 0;
})->purpose('Проверить основные настройки перед запуском в production');
