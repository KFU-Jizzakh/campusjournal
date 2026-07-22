<?php

namespace App\Filament\Resources;

use App\Enums\ArticleFileType;
use App\Enums\ArticleStatus;
use App\Filament\Resources\ArticleResource\Pages;
use App\Jobs\DepositArticleToCrossref;
use App\Models\Article;
use App\Models\CrossrefDeposit;
use App\Services\Jats\JatsXmlBuilder;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Статьи';

    protected static ?string $modelLabel = 'Статья';

    protected static ?string $pluralModelLabel = 'Статьи';

    protected static string|\UnitEnum|null $navigationGroup = 'Контент';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Внимание')
                ->description('Загруженный файл JATS XML содержит ошибки и не используется. Статья экспортируется в автоматически сгенерированном XML. Пожалуйста, проверьте и исправьте файл.')
                ->icon('heroicon-o-exclamation-triangle')
                ->iconColor('warning')
                ->visible(fn (?Article $record) => $record !== null && ! app(JatsXmlBuilder::class)->hasValidUploadedOverride($record))
                ->columnSpanFull(),
            Forms\Components\TextInput::make('title')->label('Название')->required()->maxLength(500)->columnSpanFull(),
            Forms\Components\Select::make('category_id')->label('Рубрика')->relationship('category', 'name')->searchable()->preload(),
            Forms\Components\Select::make('issue_id')->label('Выпуск')->relationship('issue', 'title')->searchable()->preload(),
            Forms\Components\Select::make('status')->label('Статус')->options(
                collect(ArticleStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])
            )->default(ArticleStatus::Draft->value)->required()->reactive(),
            Forms\Components\DateTimePicker::make('copyedited_at')->label('Дата корректуры')->visible(fn ($get) => in_array($get('status'), ['copyediting', 'production', 'published'])),
            Forms\Components\Select::make('copyedited_by')->label('Корректор')->relationship('copyeditedBy', 'email')->searchable()->preload()->visible(fn ($get) => in_array($get('status'), ['copyediting', 'production', 'published'])),
            Forms\Components\DateTimePicker::make('production_at')->label('Дата производства')->visible(fn ($get) => in_array($get('status'), ['production', 'published'])),
            Forms\Components\Select::make('production_by')->label('В производство')->relationship('productionBy', 'email')->searchable()->preload()->visible(fn ($get) => in_array($get('status'), ['production', 'published'])),
            Forms\Components\Select::make('submitted_by')->label('Подал')->relationship('submitter', 'email')->searchable()->preload(),
            Forms\Components\Textarea::make('abstract_ru')->label('Аннотация (RU)')->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('abstract_en')->label('Abstract (EN)')->rows(4)->columnSpanFull(),
            Forms\Components\Repeater::make('references')
                ->label('Список литературы')
                ->relationship('references')
                ->orderColumn('order')
                ->schema([
                    Forms\Components\Textarea::make('raw')
                        ->label('Текст ссылки')
                        ->required()
                        ->rows(2),
                    Forms\Components\TextInput::make('doi')
                        ->label('DOI')
                        ->disabled(),
                    Forms\Components\TextInput::make('cited_count')
                        ->label('Цитирований')
                        ->disabled()
                        ->numeric(),
                ])
                ->columnSpanFull()
                ->defaultItems(0)
                ->addActionLabel('Добавить ссылку'),
            Forms\Components\RichEditor::make('body')
                ->label('Текст статьи')
                ->columnSpanFull()
                ->toolbarButtons([
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'h2',
                    'h3',
                    'blockquote',
                    'bulletList',
                    'orderedList',
                    'link',
                    'code',
                    'codeBlock',
                ]),
            Forms\Components\Select::make('authors')
                ->label('Авторы')
                ->relationship('authors', 'full_name')
                ->multiple()
                ->searchable()
                ->preload()
                ->columnSpanFull(),
            Forms\Components\TextInput::make('doi')->label('DOI')->maxLength(255),
            Forms\Components\TextInput::make('pages')->label('Страницы')->maxLength(50),
            Forms\Components\TextInput::make('first_page')->label('Первая страница')->maxLength(50),
            Forms\Components\TextInput::make('last_page')->label('Последняя страница')->maxLength(50),
            Forms\Components\DateTimePicker::make('doi_registered_at')->label('DOI зарегистрирован')->disabled(),
            Forms\Components\TagsInput::make('keywords')->label('Ключевые слова')->columnSpanFull(),
            Forms\Components\FileUpload::make('pdf_path')->label('PDF')->disk('public')->directory('articles')->acceptedFileTypes(['application/pdf']),
            Forms\Components\DateTimePicker::make('submitted_at')->label('Дата подачи'),
            Forms\Components\DateTimePicker::make('published_at')->label('Дата публикации'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Название')->limit(50)->searchable(),
                Tables\Columns\TextColumn::make('authors.full_name')->label('Авторы')->limit(50),
                Tables\Columns\TextColumn::make('category.name')->label('Рубрика')->sortable(),
                Tables\Columns\TextColumn::make('issue.title')->label('Выпуск')->limit(30),
                Tables\Columns\TextColumn::make('status')->badge()->label('Статус')
                    ->color(fn ($state) => $state instanceof ArticleStatus ? $state->color() : 'gray')
                    ->formatStateUsing(fn ($state) => $state instanceof ArticleStatus ? $state->label() : $state),
                Tables\Columns\TextColumn::make('jats_override_invalid')
                    ->label('JATS')
                    ->badge()
                    ->color('warning')
                    ->state(fn (Article $record) => $record->files->contains(fn ($f) => $f->file_type === ArticleFileType::JatsXml->value) && ! app(JatsXmlBuilder::class)->hasValidUploadedOverride($record) ? 'XML ошибка' : null),
                Tables\Columns\TextColumn::make('latestCrossrefDeposit.status')->label('Crossref')->badge()->colors([
                    'gray' => 'pending',
                    'info' => 'submitted',
                    'success' => 'accepted',
                    'danger' => 'failed',
                ]),
                Tables\Columns\TextColumn::make('views_count')->label('Просмотры')->sortable(),
                Tables\Columns\TextColumn::make('downloads_count')->label('Скачивания')->sortable(),
                Tables\Columns\TextColumn::make('submitted_at')->label('Подана')->date('d.m.Y')->sortable(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->with(['files']))
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Статус')->options(
                    collect(ArticleStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                ),
                Tables\Filters\SelectFilter::make('category_id')->label('Рубрика')->relationship('category', 'name'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\Action::make('depositCrossref')
                    ->label('Crossref')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Article $record) => $record->status === ArticleStatus::Published && auth()->user()?->can('create', CrossrefDeposit::class))
                    ->requiresConfirmation()
                    ->action(function (Article $record) {
                        DepositArticleToCrossref::dispatch($record, auth()->id());
                        Notification::make()
                            ->title('Депонирование в Crossref поставлено в очередь')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
