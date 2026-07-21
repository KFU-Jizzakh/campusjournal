<?php

namespace App\Filament\Resources;

use App\Enums\Country;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 11;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')
                ->label('Пароль')
                ->password()
                ->required()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->visibleOn('create'),
            Forms\Components\CheckboxList::make('roles')
                ->label('Роли')
                ->relationship('roles', 'name')
                ->columnSpanFull(),
            Section::make('Профиль')
                ->relationship('profile')
                ->schema([
                    Forms\Components\TextInput::make('last_name')->label('Фамилия')->required()->maxLength(255),
                    Forms\Components\TextInput::make('first_name')->label('Имя')->required()->maxLength(255),
                    Forms\Components\TextInput::make('middle_name')->label('Отчество')->maxLength(255),
                    Forms\Components\TextInput::make('affiliation')->label('Место работы')->maxLength(255),
                    Forms\Components\Select::make('country')
                        ->label('Страна')
                        ->options(collect(Country::cases())->pluck('value', 'value')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profile.last_name')
                    ->label('Фамилия')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('profile.first_name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Роли')
                    ->badge(),
            ])
            ->defaultSort('profile.last_name')
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
