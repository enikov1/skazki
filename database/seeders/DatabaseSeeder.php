<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $adminEmail = config('initial-admin.email');
        $adminPassword = config('initial-admin.password');
        if (filled($adminEmail) && filled($adminPassword)) {
            $admin = User::firstOrCreate(['email' => $adminEmail], ['name' => config('initial-admin.name') ?: 'Администратор', 'password' => $adminPassword, 'email_verified_at' => now()]);
            $admin->roles()->syncWithoutDetaching([Role::where('slug', 'admin')->value('id')]);
        }
        if (app()->environment('local', 'testing') && Story::count() === 0) {
            Story::create(['title' => 'Колобок', 'slug' => 'kolobok', 'author' => 'Русская народная сказка', 'genre' => 'Народные сказки', 'summary' => 'Добрая история о круглом хлебце, который отправился путешествовать.', 'body' => 'Жили-были старик со старухой. Попросил старик испечь колобок. Старуха по коробу поскребла, по сусеку помела, собрала муки горсти две и замесила тесто. Испекла колобок и положила на окошко простыть. Колобок полежал-полежал, да вдруг покатился — с окна на лавку, с лавки на пол, по полу к двери, перепрыгнул порог — и покатился по дорожке.', 'age_min' => 3, 'age_max' => 7, 'reading_minutes' => 5, 'cover_path' => 'demo/assets/covers/kolobok.jpg', 'status' => 'published', 'published_at' => now()]);
        }
    }
}
