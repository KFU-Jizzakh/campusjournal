<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConferenceResource\Pages;
use App\Models\Conference;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ConferenceResource extends Resource
{
    protected static ?string $model = Conference::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Конференции';

    protected static ?string $modelLabel = 'Конференция';

    protected static ?string $pluralModelLabel = 'Конференции';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('slug')->label('Slug')->required()->maxLength(255)->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->label('Краткое описание')->rows(3)->columnSpanFull(),
            Forms\Components\RichEditor::make('body')->label('Полное описание')->columnSpanFull(),
            Forms\Components\DatePicker::make('event_date')->label('Дата начала')->required(),
            Forms\Components\DatePicker::make('event_end_date')->label('Дата окончания'),
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
                Tables\Columns\TextColumn::make('location')->label('Место')->limit(30)->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Опубликовано')->boolean()->sortable(),
            ])
            ->defaultSort('event_date', 'desc')
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConferences::route('/'),
            'create' => Pages\CreateConference::route('/create'),
            'edit' => Pages\EditConference::route('/{record}/edit'),
        ];
    }
}
