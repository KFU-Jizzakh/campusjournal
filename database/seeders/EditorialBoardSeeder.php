<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\EditorialBoardMember;
use Illuminate\Database\Seeder;

class EditorialBoardSeeder extends Seeder
{
    public function run(): void
    {
        $galimov = Author::where('full_name', 'Галимов Алмаз Мирзанурович')->first();
        $kolesnikova = Author::where('full_name', 'Колесникова Галина Ивановна')->first();
        $shagaeva = Author::where('full_name', 'Шагаева Алия Юнусовна')->first();

        if ($galimov) {
            EditorialBoardMember::firstOrCreate(
                ['author_id' => $galimov->id],
                ['role' => 'Главный редактор', 'sort_order' => 1]
            );
        }

        if ($kolesnikova) {
            EditorialBoardMember::firstOrCreate(
                ['author_id' => $kolesnikova->id],
                ['role' => 'Член редколлегии', 'sort_order' => 2]
            );
        }

        if ($shagaeva) {
            EditorialBoardMember::firstOrCreate(
                ['author_id' => $shagaeva->id],
                ['role' => 'Член редколлегии', 'sort_order' => 3]
            );
        }
    }
}
