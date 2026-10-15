<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:skazkomir', function () {
    $this->info('СказкоМир — Laravel site');
})->purpose('Проверить, что консоль приложения доступна');
