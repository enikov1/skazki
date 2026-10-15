<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Genre;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        Genre::firstOrCreate(['name' => 'Народные сказки']);
        $adminName = trim((string) config('initial-admin.name'));
        $adminEmail = Str::lower(trim((string) config('initial-admin.email')));
        $adminPassword = (string) config('initial-admin.password');
        if (filled($adminName) || filled($adminEmail) || filled($adminPassword)) {
            if (blank($adminName) || blank($adminEmail) || blank($adminPassword)) {
                throw new RuntimeException('Для первого администратора задайте все три значения: ADMIN_NAME, ADMIN_EMAIL и ADMIN_PASSWORD.');
            }
            if (mb_strlen($adminPassword) < 12) {
                throw new RuntimeException('ADMIN_PASSWORD должен содержать не менее 12 символов.');
            }

            // Intentionally refresh an existing account when the one-time deployment secret is supplied.
            // Remove ADMIN_PASSWORD from runtime secrets immediately after provisioning.
            $admin = User::updateOrCreate(['email' => $adminEmail], [
                'name' => $adminName,
                'password' => $adminPassword,
                'email_verified_at' => now(),
                'password_login_enabled' => true,
            ]);
            $admin->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);
            $this->command?->info('Доступ администратора настроен для '.$adminEmail.'. Для входа используйте email и ADMIN_PASSWORD.');
        } elseif (! app()->environment('testing')) {
            $this->command?->warn('Учётка администратора не изменена: ADMIN_NAME, ADMIN_EMAIL и ADMIN_PASSWORD не загружены. Проверьте runtime secrets и очистите config cache перед db:seed.');
        }
        if (app()->environment('local', 'testing') && Story::count() === 0) {
            Story::create(['title' => 'Колобок', 'slug' => 'kolobok', 'author' => 'Русская народная сказка', 'genre' => 'Народные сказки', 'summary' => 'Добрая история о круглом хлебце, который отправился путешествовать.', 'body' => 'Жили-были старик со старухой. Попросил старик испечь колобок. Старуха по коробу поскребла, по сусеку помела, собрала муки горсти две и замесила тесто. Испекла колобок и положила на окошко простыть. Колобок полежал-полежал, да вдруг покатился — с окна на лавку, с лавки на пол, по полу к двери, перепрыгнул порог — и покатился по дорожке.', 'age_min' => 3, 'age_max' => 7, 'reading_minutes' => 5, 'cover_path' => 'demo/assets/covers/kolobok.jpg', 'status' => 'published', 'published_at' => now()]);
        }
    }
}
