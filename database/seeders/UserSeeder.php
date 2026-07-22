<?php

namespace Database\Seeders;

use App\Enums\Country;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@globalcampus.local',
                'role' => 'admin',
                'last_name' => 'Администратор',
                'first_name' => '',
                'middle_name' => null,
            ],
            [
                'email' => 'galimov@globalcampus.local',
                'role' => 'editor-in-chief',
                'last_name' => 'Галимов',
                'first_name' => 'Алмаз',
                'middle_name' => 'Мирзанурович',
                'affiliation' => 'Филиал Казанского федерального университета в г. Джизаке',
                'country' => Country::Uzbekistan->value,
            ],
            [
                'email' => 'managing@globalcampus.local',
                'role' => 'managing-editor',
                'last_name' => 'Редактор',
                'first_name' => 'Выпускающий',
                'middle_name' => null,
            ],
            [
                'email' => 'section@globalcampus.local',
                'role' => 'section-editor',
                'last_name' => 'Раздела',
                'first_name' => 'Редактор',
                'middle_name' => null,
            ],
            [
                'email' => 'reviewer@globalcampus.local',
                'role' => 'reviewer',
                'last_name' => 'Рецензент',
                'first_name' => 'Тестовый',
                'middle_name' => null,
            ],
            [
                'email' => 'author@globalcampus.local',
                'role' => 'author',
                'last_name' => 'Автор',
                'first_name' => 'Тестовый',
                'middle_name' => null,
            ],
            [
                'email' => 'content@globalcampus.local',
                'role' => 'content-manager',
                'last_name' => 'Контент',
                'first_name' => 'Менеджер',
                'middle_name' => null,
            ],
            [
                'email' => 'reviewer2@globalcampus.local',
                'role' => 'reviewer',
                'last_name' => 'Рецензент',
                'first_name' => 'Второй',
                'middle_name' => null,
            ],
            [
                'email' => 'author2@globalcampus.local',
                'role' => 'author',
                'last_name' => 'Исследователь',
                'first_name' => 'Тестовый',
                'middle_name' => null,
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'password' => bcrypt('password1234'),
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole($data['role']);

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'last_name' => $data['last_name'],
                    'first_name' => $data['first_name'],
                    'middle_name' => $data['middle_name'],
                    'affiliation' => $data['affiliation'] ?? null,
                    'country' => $data['country'] ?? null,
                ]
            );
        }
    }
}
