<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $reviewer = User::where('email', 'reviewer@globalcampus.local')->first();
        $reviewer2 = User::where('email', 'reviewer2@globalcampus.local')->first();
        $section = User::where('email', 'section@globalcampus.local')->first();

        // Статьи in_review — рецензии в процессе
        $articlesInReview = Article::where('status', ArticleStatus::InReview)->get();

        if ($articlesInReview->count() >= 1) {
            $art = $articlesInReview[0]; // Олимпиадное движение
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'pending',
                    'assigned_at' => now()->subDays(5),
                    'response_due_at' => now()->addDays(2), // 2 дня на ответ
                    'review_due_at' => now()->addDays(25), // 25 дней на рецензию
                ]
            );
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer2?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'in_progress',
                    'assigned_at' => now()->subDays(5),
                    'response_due_at' => now()->subDays(3), // уже принял
                    'review_due_at' => now()->addDays(20), // 20 дней на рецензию
                ]
            );
        }

        if ($articlesInReview->count() >= 2) {
            $art = $articlesInReview[1]; // Методы оценки
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'in_progress',
                    'assigned_at' => now()->subDays(4),
                    'response_due_at' => now()->subDays(2),
                    'review_due_at' => now()->addDays(26), // 26 дней на рецензию
                ]
            );
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer2?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'pending',
                    'assigned_at' => now()->subDays(3),
                    'response_due_at' => now()->addDays(4), // 4 дня на ответ
                    'review_due_at' => now()->addDays(27), // 27 дней на рецензию
                ]
            );
        }

        // Статья revision — завершённая рецензия с major_revision
        $articleRevision = Article::where('status', ArticleStatus::Revision)->first();
        if ($articleRevision) {
            Review::firstOrCreate(
                ['article_id' => $articleRevision->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'major_revision',
                    'comments_for_author' => 'Необходимо усилить эмпирическую базу: добавить конкретные статистические данные по выпускникам, показатели трудоустройства. Рекомендуется также структурировать интервью в формате Q&A.',
                    'comments_for_editor' => 'Материал представляет интерес, но нуждается в серьёзной доработке. Формат интервью следует дополнить аналитическим комментарием.',
                    'assigned_at' => now()->subDays(20),
                    'completed_at' => now()->subDays(8),
                    'response_due_at' => now()->subDays(17),
                    'review_due_at' => now()->subDays(8),
                ]
            );
        }

        // Статьи accepted — завершённые рецензии
        $articlesAccepted = Article::where('status', ArticleStatus::Accepted)->get();

        if ($articlesAccepted->count() >= 1) {
            $art = $articlesAccepted[0]; // Методика обучения
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'accept',
                    'comments_for_author' => 'Методика хорошо обоснована, результаты апробации убедительны. Рекомендуется к публикации.',
                    'comments_for_editor' => 'Сильная методическая работа с ясной новизной. Рекомендую принять.',
                    'assigned_at' => now()->subDays(30),
                    'completed_at' => now()->subDays(10),
                    'response_due_at' => now()->subDays(27),
                    'review_due_at' => now()->subDays(10),
                ]
            );
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer2?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'minor_revision',
                    'comments_for_author' => 'Рекомендуется добавить сравнение с существующими аналогичными методиками. В остальном работа выполнена на высоком уровне.',
                    'comments_for_editor' => 'Незначительные замечания, которые не влияют на общее качество статьи. Можно принять после мелких правок или без них.',
                    'assigned_at' => now()->subDays(30),
                    'completed_at' => now()->subDays(12),
                    'response_due_at' => now()->subDays(27),
                    'review_due_at' => now()->subDays(12),
                ]
            );
        }

        if ($articlesAccepted->count() >= 2) {
            $art = $articlesAccepted[1]; // Психологические аспекты
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'accept',
                    'comments_for_author' => 'Исследование выполнено на высоком методологическом уровне. Результаты представляют значительный практический интерес для преподавателей филиалов.',
                    'comments_for_editor' => 'Междисциплинарный подход, качественная эмпирическая база. Рекомендую к публикации.',
                    'assigned_at' => now()->subDays(25),
                    'completed_at' => now()->subDays(8),
                    'response_due_at' => now()->subDays(22),
                    'review_due_at' => now()->subDays(8),
                ]
            );
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer2?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'accept',
                    'comments_for_author' => 'Тема весьма актуальна. Предложенные методы снижения тревожности практичны и могут быть внедрены. Статья рекомендуется к публикации.',
                    'comments_for_editor' => 'Обе рецензии положительные, статья готова к публикации.',
                    'assigned_at' => now()->subDays(25),
                    'completed_at' => now()->subDays(6),
                    'response_due_at' => now()->subDays(22),
                    'review_due_at' => now()->subDays(6),
                ]
            );
        }

        // Статья rejected — завершённая рецензия с reject
        $articleRejected = Article::where('status', ArticleStatus::Rejected)->first();
        if ($articleRejected) {
            Review::firstOrCreate(
                ['article_id' => $articleRejected->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'reject',
                    'comments_for_author' => 'Сравнительный анализ носит преимущественно описательный характер. Отсутствуют критерии сравнения, не обоснована выборка филиалов, выводы не подкреплены данными.',
                    'comments_for_editor' => 'Работа не соответствует минимальным требованиям к научной публикации: нет методологии, нет новизны, формальное перечисление без анализа.',
                    'assigned_at' => now()->subDays(30),
                    'completed_at' => now()->subDays(15),
                    'response_due_at' => now()->subDays(27),
                    'review_due_at' => now()->subDays(15),
                ]
            );
            Review::firstOrCreate(
                ['article_id' => $articleRejected->id, 'reviewer_id' => $reviewer2?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'reject',
                    'comments_for_author' => 'Недостаточно раскрыта методология исследования. Отсутствует анализ нормативных документов, не указаны источники данных.',
                    'comments_for_editor' => 'Согласен с первым рецензентом. Статья требует принципиальной переработки.',
                    'assigned_at' => now()->subDays(30),
                    'completed_at' => now()->subDays(13),
                    'response_due_at' => now()->subDays(27),
                    'review_due_at' => now()->subDays(13),
                ]
            );
        }

        // Статьи published — завершённые рецензии
        $articlesPublished = Article::where('status', ArticleStatus::Published)->get();

        foreach ($articlesPublished as $i => $art) {
            Review::firstOrCreate(
                ['article_id' => $art->id, 'reviewer_id' => $reviewer?->id],
                [
                    'assigned_by' => $section?->id,
                    'status' => 'completed',
                    'recommendation' => 'accept',
                    'comments_for_author' => 'Статья выполнена на высоком уровне и рекомендуется к публикации без замечаний.',
                    'comments_for_editor' => 'Качественная работа, соответствует профилю журнала.',
                    'assigned_at' => now()->subDays(45 - $i * 5),
                    'completed_at' => now()->subDays(25 - $i * 3),
                    'response_due_at' => now()->subDays(42 - $i * 5),
                    'review_due_at' => now()->subDays(25 - $i * 3),
                ]
            );
        }
    }
}
