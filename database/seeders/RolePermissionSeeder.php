<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'stories.manage' => 'Создавать и редактировать сказки',
            'stories.publish' => 'Публиковать сказки',
            'comments.moderate' => 'Проверять комментарии',
            'users.manage' => 'Управлять пользователями и ролями',
            'reports.view' => 'Просматривать статистику сайта',
        ];
        foreach ($permissions as $slug => $name) Permission::firstOrCreate(['slug' => $slug], ['name' => $name]);

        $roles = [
            'user' => ['Пользователь', 'Читает сказки, сохраняет понравившееся и оставляет комментарии.'],
            'editor' => ['Редактор', 'Готовит и обновляет материалы библиотеки.'],
            'moderator' => ['Модератор', 'Проверяет комментарии и пользовательские материалы.'],
            'admin' => ['Администратор', 'Полный доступ к управлению сайтом и сотрудниками.'],
        ];
        foreach ($roles as $slug => [$name, $description]) Role::firstOrCreate(['slug' => $slug], compact('name', 'description'));

        Role::where('slug', 'editor')->firstOrFail()->permissions()->sync(Permission::whereIn('slug', ['stories.manage'])->pluck('id'));
        Role::where('slug', 'moderator')->firstOrFail()->permissions()->sync(Permission::whereIn('slug', ['comments.moderate'])->pluck('id'));
        Role::where('slug', 'admin')->firstOrFail()->permissions()->sync(Permission::pluck('id'));
    }
}
