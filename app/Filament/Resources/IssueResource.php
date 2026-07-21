<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IssueResource\Pages;
use App\Models\Issue;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class IssueResource extends Resource
{
    protected static ?string $model = Issue::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Выпуски';

    protected static ?string $modelLabel = 'Выпуск';

    protected static ?string $pluralModelLabel = 'Выпуски';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('volume')->label('Том')->numeric()->required(),
            Forms\Components\TextInput::make('number')->label('Номер')->numeric()->required(),
            Forms\Components\TextInput::make('year')->label('Год')->numeric()->required(),
            Forms\Components\TextInput::make('title')->label('Название')->maxLength(255)->columnSpanFull(),
            Forms\Components\TextInput::make('theme')->label('Тема')->maxLength(255)->columnSpanFull(),
            Forms\Components\Textarea::make('description')->label('Описание')->rows(4)->columnSpanFull(),
            Forms\Components\FileUpload::make('cover_path')->label('Обложка')->disk('public')->directory('issues/covers')->image(),
            Forms\Components\FileUpload::make('pdf_path')->label('PDF')->disk('public')->directory('issues/pdf')->acceptedFileTypes(['application/pdf']),
            Forms\Components\DatePicker::make('published_at')->label('Дата публикации'),
            Forms\Components\TextInput::make('doi')->label('DOI')->maxLength(255),
            Forms\Components\Select::make('status')->label('Статус')->options([
                'planned' => 'Запланирован',
                'in_progress' => 'В работе',
                'published' => 'Опубликован',
            ])->default('planned')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('number')->label('Номер / Год')
                    ->formatStateUsing(fn ($record) => "#{$record->number} ({$record->year})")
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->label('Статус')->colors([
                    'gray' => 'planned',
                    'warning' => 'in_progress',
                    'success' => 'published',
                ])->formatStateUsing(fn ($state) => match ($state) {
                    'planned' => 'Запланирован', 'in_progress' => 'В работе',
                    'published' => 'Опубликован', default => $state,
                }),
                Tables\Columns\TextColumn::make('published_at')->label('Дата публикации')->date('d.m.Y')->sortable(),
            ])
            ->filters([])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIssues::route('/'),
            'create' => Pages\CreateIssue::route('/create'),
            'edit' => Pages\EditIssue::route('/{record}/edit'),
        ];
    }
}
