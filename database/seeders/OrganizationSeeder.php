<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $organizations = [
            [
                'name' => 'Филиал КФУ в г. Джизаке',
                'description' => 'Филиал Казанского федерального университета в г. Джизаке (Республика Узбекистан) — учредитель и издатель журнала «Global Campus RU».',
                'url' => 'https://kpfu.ru/dzhizak',
                'sort_order' => 1,
            ],
            [
                'name' => 'Казанский федеральный университет',
                'description' => 'Один из ведущих федеральных университетов России, головной вуз филиала в г. Джизаке.',
                'url' => 'https://kpfu.ru',
                'sort_order' => 2,
            ],
            [
                'name' => 'Ассоциация «Содружество образовательных организаций им. А. С. Пушкина и педагогов»',
                'description' => 'Международная ассоциация, объединяющая образовательные организации и педагогов в целях сохранения и укрепления традиционных российских духовно-нравственных ценностей.',
                'url' => 'https://sooiaspp.ru',
                'sort_order' => 3,
            ],
        ];

        foreach ($organizations as $org) {
            Organization::firstOrCreate(
                ['name' => $org['name']],
                $org
            );
        }
    }
}
