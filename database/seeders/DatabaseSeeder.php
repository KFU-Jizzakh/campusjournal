<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            CopyrightAgreementSeeder::class,
            RoleSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            PageSeeder::class,
            OrganizationSeeder::class,
            AuthorSeeder::class,
            EditorialBoardSeeder::class,
            IssueSeeder::class,
            EventSeeder::class,
            ConferenceSeeder::class,
            NewsSeeder::class,
            ArticleSeeder::class,
            ArticleFileSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
