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
            'bibtex_key_prefix' => '',
            'reviewer_self_registration' => '1',
            'decision_template_accept' => "Уважаемые авторы!\n\nРедакция рада сообщить, что ваша статья «{title}» (рукопись №{id}) принята к публикации.\n\nС уважением,\n{editor}",
            'decision_template_revision' => "Уважаемые авторы!\n\nПо результатам рецензирования статья «{title}» (рукопись №{id}) требует доработки. Просим внести правки с учётом замечаний рецензентов и направить ответы на их комментарии.\n\nС уважением,\n{editor}",
            'decision_template_reject' => "Уважаемые авторы!\n\nК сожалению, статья «{title}» (рукопись №{id}) не принята к публикации. Благодарим за интерес к журналу.\n\nС уважением,\n{editor}",
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
