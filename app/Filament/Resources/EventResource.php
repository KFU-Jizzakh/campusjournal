<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'События';

    protected static ?string $modelLabel = 'Событие';

    protected static ?string $pluralModelLabel = 'События';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
            Forms\Components\DatePicker::make('event_date')->label('Дата начала')->required(),
            Forms\Components\DatePicker::make('event_end_date')->label('Дата окончания'),
            Forms\Components\Select::make('event_type')->label('Тип события')->options([
                'conference' => 'Конференция',
                'forum' => 'Форум',
                'deadline' => 'Дедлайн',
                'webinar' => 'Вебинар',
            ])->required(),
            Forms\Components\TextInput::make('location')->label('Место проведения')->maxLength(255),
            Forms\Components\TextInput::make('url')->label('Ссылка')->url()->maxLength(255),
            Forms\Components\Toggle::make('is_published')->label('Опубликовано')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название')->limit(50)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('event_date')->label('Дата')->date('d.m.Y')->sortable(),
                Tables\Columns\TextColumn::make('event_type')->label('Тип')->formatStateUsing(fn ($state) => match ($state) {
                    'conference' => 'Конференция', 'forum' => 'Форум',
                    'deadline' => 'Дедлайн', 'webinar' => 'Вебинар', default => $state,
                })->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Опубликовано')->boolean()->sortable(),
            ])
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
