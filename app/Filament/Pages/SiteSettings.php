<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Rules\BibtexKeyPrefix;
use App\Rules\Issn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiteSettings extends Page
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Настройки сайта';

    protected static ?string $title = 'Настройки сайта';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.site-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'contact_email' => Setting::get('contact_email', 'liceum9zd@yandex.ru'),
            'contact_phone' => Setting::get('contact_phone', '+7 (995) 285-83-21'),
            'contact_phone_raw' => Setting::get('contact_phone_raw', '+79952858321'),
            'social_vk' => Setting::get('social_vk', 'https://vk.com/public220986216'),
            'social_telegram' => Setting::get('social_telegram', 'https://t.me/asooaspp'),
            'social_whatsapp' => Setting::get('social_whatsapp', 'https://api.whatsapp.com/message/5YSFA5VES7O2J1'),
            'social_rutube' => Setting::get('social_rutube', 'https://rutube.ru/channel/26854854'),
            'review_response_days' => Setting::get('review_response_days', '7'),
            'review_deadline_days' => Setting::get('review_deadline_days', '30'),
            'journal_issn_print' => Setting::get('journal_issn_print', ''),
            'journal_issn_electronic' => Setting::get('journal_issn_electronic', ''),
            'bibtex_key_prefix' => Setting::get('bibtex_key_prefix', ''),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Контактная информация')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('Телефон (отображаемый)')
                            ->maxLength(255)
                            ->placeholder('+7 (999) 123-45-67'),
                        TextInput::make('contact_phone_raw')
                            ->label('Телефон (для ссылки tel:)')
                            ->maxLength(255)
                            ->placeholder('+79991234567'),
                    ])->columns(3),

                Section::make('Социальные сети')
                    ->schema([
                        TextInput::make('social_vk')
                            ->label('VK')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_telegram')
                            ->label('Telegram')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_whatsapp')
                            ->label('WhatsApp')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('social_rutube')
                            ->label('RuTube')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),

                Section::make('Рецензирование')
                    ->schema([
                        TextInput::make('review_response_days')
                            ->label('Дней на ответ (принять/отклонить)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(30)
                            ->required(),
                        TextInput::make('review_deadline_days')
                            ->label('Дней на рецензию')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(90)
                            ->required(),
                    ])->columns(2),

                Section::make('Информация о журнале')
                    ->schema([
                        TextInput::make('journal_issn_print')
                            ->label('Печатный ISSN (p-ISSN)')
                            ->rules([new Issn])
                            ->maxLength(9)
                            ->placeholder('1234-5678'),
                        TextInput::make('journal_issn_electronic')
                            ->label('Электронный ISSN (e-ISSN)')
                            ->rules([new Issn])
                            ->maxLength(9)
                            ->placeholder('1234-5679'),
                        TextInput::make('bibtex_key_prefix')
                            ->label('Префикс ключей BibTeX')
                            ->rules([new BibtexKeyPrefix])
                            ->maxLength(32)
                            ->placeholder('gcru'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        Notification::make()
            ->title('Настройки сохранены')
            ->success()
            ->send();
    }
}
