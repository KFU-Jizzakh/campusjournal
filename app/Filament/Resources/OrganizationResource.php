<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Организации';

    protected static ?string $modelLabel = 'Организация';

    protected static ?string $pluralModelLabel = 'Организации';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')->label('Название')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
            Forms\Components\FileUpload::make('logo_path')->label('Логотип')->disk('public')->directory('organizations/logos')->image(),
            Forms\Components\TextInput::make('url')->label('Сайт')->url()->maxLength(255),
            Forms\Components\TextInput::make('sort_order')->label('Порядок сортировки')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Название')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('url')->label('Сайт')->limit(40)->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Порядок')->sortable(),
            ])
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
