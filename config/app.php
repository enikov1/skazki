<?php

return [
    'name' => env('APP_NAME', 'СказкоМир'), 'env' => env('APP_ENV', 'production'), 'debug' => (bool) env('APP_DEBUG', false), 'url' => env('APP_URL', 'http://localhost'), 'timezone' => 'Europe/Moscow', 'locale' => 'ru', 'fallback_locale' => 'ru', 'faker_locale' => 'ru_RU', 'key' => env('APP_KEY'), 'cipher' => 'AES-256-CBC',
    'maintenance' => ['driver' => env('APP_MAINTENANCE_DRIVER', 'file'), 'store' => env('APP_MAINTENANCE_STORE', 'database')],
];
