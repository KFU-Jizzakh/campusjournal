<?php

namespace App\Filament\Resources;

use App\Enums\ArticleFileLicense;
use App\Filament\Resources\CopyrightAgreementResource\Pages;
use App\Models\CopyrightAgreement;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * PURPOSE: Filament admin resource for versioned copyright
 * agreement templates. Manages creation, editing, and
 * activation/deletion of agreement versions for
 * author acceptance during submission.
 *
 * SPECIFICATION: SPEC-14/AC-5, SPEC-14/BR-3, SPEC-14/BR-4
 */
class CopyrightAgreementResource extends Resource
{
    protected static ?string $model = CopyrightAgreement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Лицензионное соглашение';

    protected static ?string $modelLabel = 'Соглашение';

    protected static ?string $pluralModelLabel = 'Лицензионные соглашения';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 30;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('version')
                ->label('Версия')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->default(fn () => CopyrightAgreement::nextVersion()),

            Forms\Components\TextInput::make('title')
                ->label('Заголовок')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('short_text')
                ->label('Краткий текст (для формы подачи)')
                ->required()
                ->rows(6)
                ->columnSpanFull(),

            Forms\Components\RichEditor::make('full_text')
                ->label('Полный текст соглашения')
                ->nullable()
                ->columnSpanFull(),

            Forms\Components\Select::make('license')
                ->label('Тип лицензии')
                ->options(collect(ArticleFileLicense::cases())->mapWithKeys(fn ($l) => [$l->value => $l->label()]))
                ->nullable()
                ->placeholder('— Выберите лицензию —')
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_active')
                ->label('Активная версия')
                ->helperText('Только одна версия может быть активной. При активации остальные деактивируются.')
                ->default(false)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->label('Версия')
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Заголовок')
                    ->searchable()
                    ->sortable()
                    ->limit(60),

                Tables\Columns\TextColumn::make('license')
                    ->label('Лицензия')
                    ->formatStateUsing(fn ($state) => $state?->label() ?? '—'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Опубликована')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('agreements_count')
                    ->label('Принятий')
                    ->counts('agreements')
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()
                    ->hidden(fn (CopyrightAgreement $record) => $record->agreements()->exists()),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCopyrightAgreements::route('/'),
            'create' => Pages\CreateCopyrightAgreement::route('/create'),
            'edit' => Pages\EditCopyrightAgreement::route('/{record}/edit'),
        ];
    }
}
