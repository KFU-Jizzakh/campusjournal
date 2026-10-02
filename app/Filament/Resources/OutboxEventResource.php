<?php

namespace App\Filament\Resources;

use App\Models\OutboxEvent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * PURPOSE: Read-only audit log viewer — every domain event with actor,
 * subject, and payload snapshot. Admin-only.
 */
class OutboxEventResource extends Resource
{
    protected static ?string $model = OutboxEvent::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Журнал событий';

    protected static ?string $modelLabel = 'Событие';

    protected static ?string $pluralModelLabel = 'Журнал событий';

    protected static string|\UnitEnum|null $navigationGroup = 'Настройки';

    protected static ?int $navigationSort = 12;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Событие')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Объект')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : Str::afterLast($state, '\\')),
                Tables\Columns\TextColumn::make('subject_id')
                    ->label('ID объекта'),
                Tables\Columns\TextColumn::make('actor.email')
                    ->label('Актор')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('payload')
                    ->label('Данные')
                    ->formatStateUsing(fn ($state): string => $state ? Str::limit(json_encode($state, JSON_UNESCAPED_UNICODE), 80) : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => OutboxEventResource\Pages\ListOutboxEvents::route('/'),
        ];
    }
}
