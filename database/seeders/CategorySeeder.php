<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Научная секция',
                'description' => 'Рецензируемые статьи по педагогике, методике преподавания РКИ, управлению образованием, кросс-культурной коммуникации.',
            ],
            [
                'name' => 'Практика филиала',
                'description' => 'Интервью с руководителями, кейсы успешных проектов, адаптация учебных программ.',
            ],
            [
                'name' => 'Методическая копилка',
                'description' => 'Лучшие практики преподавания различных дисциплин на русском языке.',
            ],
            [
                'name' => 'Русский язык в мире науки',
                'description' => 'Материалы о роли русского языка в профессиональной подготовке.',
            ],
            [
                'name' => 'Студенческий взгляд',
                'description' => 'Эссе, проекты и мнения учащихся филиалов.',
            ],
            [
                'name' => 'От редакции',
                'description' => 'Введение в тему номера, слово главного редактора.',
            ],
            [
                'name' => 'Новости сети',
                'description' => 'Короткие заметки о событиях в разных филиалах.',
            ],
            [
                'name' => 'Рецензия на книгу',
                'description' => 'Актуальная монография по тематике филиалов или преподаванию РКИ.',
            ],
        ];

        foreach ($categories as $index => $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
