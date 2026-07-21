<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'abstract_ru' => fake()->paragraph(),
            'abstract_en' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'pdf_path' => 'submissions/test.pdf',
            'status' => ArticleStatus::Draft,
            'submitted_by' => User::factory(),
        ];
    }

    public function submitted(): static
    {
        return $this->state([
            'status' => ArticleStatus::Submitted,
            'submitted_at' => now(),
        ]);
    }

    public function inReview(): static
    {
        return $this->state([
            'status' => ArticleStatus::InReview,
            'submitted_at' => now(),
        ]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => ArticleStatus::Accepted,
            'submitted_at' => now(),
            'decision' => 'accept',
            'decided_at' => now(),
            'decided_by' => User::factory(),
        ]);
    }

    public function revision(): static
    {
        return $this->state([
            'status' => ArticleStatus::Revision,
            'submitted_at' => now(),
            'decision' => 'revision',
            'decided_at' => now(),
            'decided_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => ArticleStatus::Rejected,
            'submitted_at' => now(),
            'decision' => 'reject',
            'decided_at' => now(),
            'decided_by' => User::factory(),
        ]);
    }

    public function copyediting(): static
    {
        return $this->state([
            'status' => ArticleStatus::Copyediting,
            'submitted_at' => now(),
            'decision' => 'accept',
            'decided_at' => now(),
            'decided_by' => User::factory(),
            'copyedited_at' => now(),
            'copyedited_by' => User::factory(),
        ]);
    }

    /**
     * Pre-populate the article with a copyedited file path,
     * so that sending to production passes the guard.
     */
    public function withCopyeditedFile(): static
    {
        return $this->state([
            'copyedited_file_path' => 'copyedited/test.docx',
            'copyedited_file_uploaded_at' => now(),
            'copyedited_file_uploaded_by' => null,
        ]);
    }

    public function production(): static
    {
        return $this->state([
            'status' => ArticleStatus::Production,
            'submitted_at' => now(),
            'decision' => 'accept',
            'decided_at' => now(),
            'decided_by' => User::factory(),
            'copyedited_at' => now(),
            'copyedited_by' => User::factory(),
            'production_at' => now(),
            'production_by' => User::factory(),
        ]);
    }

    public function published(): static
    {
        return $this->state([
            'status' => ArticleStatus::Published,
            'submitted_at' => now(),
            'decision' => 'accept',
            'decided_at' => now(),
            'decided_by' => User::factory(),
            'copyedited_at' => now(),
            'copyedited_by' => User::factory(),
            'production_at' => now(),
            'production_by' => User::factory(),
            'published_at' => now(),
        ]);
    }

    public function awaitingApproval(): static
    {
        return $this->state([
            'status' => ArticleStatus::AwaitingApproval,
            'submitted_at' => now(),
            'decision' => 'accept',
            'decided_at' => now(),
            'decided_by' => User::factory(),
            'copyedited_at' => now(),
            'copyedited_by' => User::factory(),
            'production_at' => now(),
            'production_by' => User::factory(),
            'galley_pdf_path' => 'galley_uploads/test.pdf',
            'galley_uploaded_at' => now(),
            'galley_uploaded_by' => User::factory(),
            'galley_sent_at' => now(),
            'galley_sent_by' => User::factory(),
        ]);
    }

    public function approved(): static
    {
        return $this->state([
            'status' => ArticleStatus::Approved,
            'submitted_at' => now(),
            'decision' => 'accept',
            'decided_at' => now(),
            'decided_by' => User::factory(),
            'copyedited_at' => now(),
            'copyedited_by' => User::factory(),
            'production_at' => now(),
            'production_by' => User::factory(),
            'galley_pdf_path' => 'galley_uploads/test.pdf',
            'galley_uploaded_at' => now(),
            'galley_uploaded_by' => User::factory(),
            'galley_sent_at' => now(),
            'galley_sent_by' => User::factory(),
            'galley_approved_at' => now(),
            'galley_approved_by' => User::factory(),
        ]);
    }
}
