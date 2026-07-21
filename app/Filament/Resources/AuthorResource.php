<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuthorResource\Pages;
use App\Models\Author;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AuthorResource extends Resource
{
    protected static ?string $model = Author::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Авторы';

    protected static ?string $modelLabel = 'Автор';

    protected static ?string $pluralModelLabel = 'Авторы';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('full_name')->label('ФИО')->required()->maxLength(255),
            Forms\Components\TextInput::make('first_name')->label('Имя (для Crossref)')->maxLength(255),
            Forms\Components\TextInput::make('last_name')->label('Фамилия (для Crossref)')->maxLength(255),
            Forms\Components\TextInput::make('degree')->label('Ученая степень')->maxLength(255),
            Forms\Components\TextInput::make('position')->label('Должность')->maxLength(255),
            Forms\Components\TextInput::make('organization')->label('Организация')->maxLength(255),
            Forms\Components\Textarea::make('bio')->label('Биография')->rows(4)->columnSpanFull(),
            Forms\Components\FileUpload::make('photo_path')->label('Фото')->disk('public')->directory('authors/photos')->image(),
            Forms\Components\TextInput::make('email')->label('Email')->email()->maxLength(255),
            Forms\Components\TextInput::make('orcid')->label('ORCID')->maxLength(255),
            Forms\Components\TextInput::make('spin_code')->label('SPIN-код')->maxLength(255),
            Forms\Components\TextInput::make('author_id_elibrary')->label('Author ID (eLIBRARY)')->maxLength(255),
            Forms\Components\TextInput::make('website')->label('Веб-сайт')->url()->maxLength(255),
            Forms\Components\Select::make('user_id')->label('Пользователь')->relationship('user', 'email')->searchable()->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('ФИО')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('degree')->label('Степень')->sortable(),
                Tables\Columns\TextColumn::make('organization')->label('Организация')->limit(30)->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->sortable(),
            ])
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuthors::route('/'),
            'create' => Pages\CreateAuthor::route('/create'),
            'edit' => Pages\EditAuthor::route('/{record}/edit'),
        ];
    }
}
