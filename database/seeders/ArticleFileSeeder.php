<?php

namespace Database\Seeders;

use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\User;
use Illuminate\Database\Seeder;

class ArticleFileSeeder extends Seeder
{
    public function run(): void
    {
        $articles = Article::all();
        $author = User::where('email', 'author@globalcampus.local')->first();
        $author2 = User::where('email', 'author2@globalcampus.local')->first();
        $section = User::where('email', 'section@globalcampus.local')->first();

        foreach ($articles as $article) {
            $this->createFilesForArticle($article, $author, $author2, $section);
        }
    }

    private function createFilesForArticle(Article $article, ?User $author, ?User $author2, ?User $section): void
    {
        $uploader = match ($article->submitted_by) {
            $author?->id => $author,
            $author2?->id => $author2,
            default => $author,
        };

        // Determine file count and types based on status
        [$fileCount, $types, $visibilities] = $this->getFileConfigForStatus($article->status);

        for ($i = 0; $i < $fileCount; $i++) {
            $type = $types[array_rand($types)];
            $visibility = $visibilities[array_rand($visibilities)];

            $this->createFile($article, $uploader, $type, $visibility);
        }
    }

    private function getFileConfigForStatus(ArticleStatus $status): array
    {
        return match ($status) {
            ArticleStatus::Draft => [
                rand(1, 2),
                [ArticleFileType::Document, ArticleFileType::Image],
                [ArticleFileVisibility::EditorialOnly],
            ],
            ArticleStatus::Submitted => [
                rand(1, 3),
                [ArticleFileType::Document, ArticleFileType::Image, ArticleFileType::ResearchData],
                [ArticleFileVisibility::EditorialOnly, ArticleFileVisibility::ReviewersOnly],
            ],
            ArticleStatus::InReview => [
                rand(2, 4),
                [ArticleFileType::Document, ArticleFileType::Image, ArticleFileType::ResearchData],
                [ArticleFileVisibility::EditorialOnly, ArticleFileVisibility::ReviewersOnly],
            ],
            ArticleStatus::Revision => [
                rand(1, 2),
                [ArticleFileType::Document],
                [ArticleFileVisibility::EditorialOnly],
            ],
            ArticleStatus::Accepted => [
                rand(2, 3),
                [ArticleFileType::Document, ArticleFileType::Image, ArticleFileType::ResearchData],
                [ArticleFileVisibility::Public, ArticleFileVisibility::EditorialOnly],
            ],
            ArticleStatus::Rejected => [
                rand(1, 2),
                [ArticleFileType::Document],
                [ArticleFileVisibility::EditorialOnly],
            ],
            ArticleStatus::Published => [
                rand(3, 5),
                [ArticleFileType::Document, ArticleFileType::Image, ArticleFileType::ResearchData],
                [ArticleFileVisibility::Public, ArticleFileVisibility::EditorialOnly, ArticleFileVisibility::ReviewersOnly],
            ],
            default => [1, [ArticleFileType::Document], [ArticleFileVisibility::EditorialOnly]],
        };
    }

    private function createFile(Article $article, User $uploader, ArticleFileType $type, ArticleFileVisibility $visibility): void
    {
        $factory = ArticleFile::factory();

        $file = match ($type) {
            ArticleFileType::Image => $factory->image($article, $uploader),
            ArticleFileType::Document => $factory->document($article, $uploader),
            ArticleFileType::ResearchData => $factory->researchData($article, $uploader),
            default => $factory->document($article, $uploader),
        };

        $file->withVisibility($visibility)->create();
    }
}
