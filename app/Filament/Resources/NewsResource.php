<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Новости';

    protected static ?string $modelLabel = 'Новость';

    protected static ?string $pluralModelLabel = 'Новости';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Заголовок')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\RichEditor::make('body')
                ->label('Текст')
                ->fileAttachmentsDisk('public')
                ->fileAttachmentsDirectory('news/attachments')
                ->columnSpanFull(),
            Forms\Components\DateTimePicker::make('published_at')->label('Дата публикации'),
            Forms\Components\Toggle::make('is_published')->label('Опубликовано')->default(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Заголовок')->limit(50)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('published_at')->label('Дата публикации')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Опубликовано')->boolean()->sortable(),
            ])
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
