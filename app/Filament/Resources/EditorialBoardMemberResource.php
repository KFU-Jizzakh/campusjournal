<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EditorialBoardMemberResource\Pages;
use App\Models\EditorialBoardMember;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EditorialBoardMemberResource extends Resource
{
    protected static ?string $model = EditorialBoardMember::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Редколлегия';

    protected static ?string $modelLabel = 'Член редколлегии';

    protected static ?string $pluralModelLabel = 'Редколлегия';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 5;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('author_id')->label('Автор')->relationship('author', 'full_name')->searchable()->preload()->required(),
            Forms\Components\TextInput::make('role')->label('Роль')->required()->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->label('Порядок сортировки')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('author.full_name')->label('ФИО')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')->label('Роль')->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEditorialBoardMembers::route('/'),
            'create' => Pages\CreateEditorialBoardMember::route('/create'),
            'edit' => Pages\EditEditorialBoardMember::route('/{record}/edit'),
        ];
    }
}
