<?php

namespace App\Filament\Resources;

use App\Enums\ReviewStatus;
use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Рецензии';

    protected static ?string $modelLabel = 'Рецензия';

    protected static ?string $pluralModelLabel = 'Рецензии';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 9;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('article_id')->label('Статья')->relationship('article', 'title')->searchable()->preload()->required(),
            Forms\Components\Select::make('reviewer_id')->label('Рецензент')->relationship('reviewer', 'email')->searchable()->preload()->required(),
            Forms\Components\Select::make('assigned_by')->label('Назначил')->relationship('assignedBy', 'email')->searchable()->preload(),
            Forms\Components\Select::make('recommendation')->label('Рекомендация')->options([
                'accept' => 'Принять',
                'minor_revision' => 'Незначительная доработка',
                'major_revision' => 'Значительная доработка',
                'reject' => 'Отклонить',
            ]),
            Forms\Components\Textarea::make('comments_for_editor')->label('Комментарии для редактора')->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('comments_for_author')->label('Комментарии для автора')->rows(4)->columnSpanFull(),
            Forms\Components\Select::make('status')->label('Статус')->options(
                collect(ReviewStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])
            )->default(ReviewStatus::Pending->value)->required(),
            Forms\Components\DateTimePicker::make('assigned_at')->label('Дата назначения'),
            Forms\Components\DateTimePicker::make('completed_at')->label('Дата завершения'),
            Section::make('Дедлайны')
                ->schema([
                    Forms\Components\DateTimePicker::make('response_due_at')->label('Ответить до'),
                    Forms\Components\DateTimePicker::make('review_due_at')->label('Рецензия до'),
                    Forms\Components\DateTimePicker::make('reminded_at')->label('Напоминание отправлено')->disabled(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('article.title')->label('Статья')->limit(40)->searchable()->sortable(),
                Tables\Columns\TextColumn::make('reviewer.name')->label('Рецензент')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('review_due_at')->label('Дедлайн')->dateTime('d.m.Y')->sortable()
                    ->badge()
                    ->color(fn ($record) => match ($record->deadlineStatus()) {
                        'overdue' => 'danger',
                        'urgent' => 'warning',
                        'warning' => 'warning',
                        'normal' => 'success',
                        'completed' => 'success',
                        'declined' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->isOverdue()) {
                            return 'Просрочено: '.$record->review_due_at->format('d.m.Y');
                        }

                        return $record->review_due_at?->format('d.m.Y') ?? '—';
                    }),
                Tables\Columns\TextColumn::make('recommendation')->badge()->label('Рекомендация')->colors([
                    'success' => 'accept',
                    'warning' => fn ($state) => in_array($state, ['minor_revision', 'major_revision']),
                    'danger' => 'reject',
                ])->formatStateUsing(fn ($state) => match ($state) {
                    'accept' => 'Принять', 'minor_revision' => 'Незнач. доработка',
                    'major_revision' => 'Знач. доработка', 'reject' => 'Отклонить', default => $state,
                }),
                Tables\Columns\TextColumn::make('status')->badge()->label('Статус')
                    ->color(fn ($state) => $state instanceof ReviewStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof ReviewStatus ? $state->label() : $state),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Статус')->options(
                    collect(ReviewStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                ),
                Tables\Filters\SelectFilter::make('recommendation')->label('Рекомендация')->options([
                    'accept' => 'Принять', 'minor_revision' => 'Незнач. доработка',
                    'major_revision' => 'Знач. доработка', 'reject' => 'Отклонить',
                ]),
                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Просроченные')
                    ->placeholder('Все')
                    ->trueLabel('Только просроченные')
                    ->falseLabel('Без просрочки')
                    ->queries(
                        true: fn ($query) => $query->overdue(),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereNull('review_due_at')
                                ->orWhere('review_due_at', '>=', now());
                        }),
                    ),
            ])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
