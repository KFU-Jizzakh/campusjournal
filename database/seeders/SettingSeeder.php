<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'contact_email' => 'liceum9zd@yandex.ru',
            'contact_phone' => '+7 (995) 285-83-21',
            'contact_phone_raw' => '+79952858321',
            'social_vk' => 'https://vk.com/public220986216',
            'social_telegram' => 'https://t.me/asooaspp',
            'social_whatsapp' => 'https://api.whatsapp.com/message/5YSFA5VES7O2J1',
            'social_rutube' => 'https://rutube.ru/channel/26854854',
            'review_response_days' => '7',
            'review_deadline_days' => '30',
            'journal_issn_print' => '',
            'journal_issn_electronic' => '',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
