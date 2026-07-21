<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\ReviewStatus;
use App\Models\Article;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewDeadlineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_review_has_deadline_fields(): void
    {
        $review = Review::factory()->create([
            'response_due_at' => now()->addDays(7),
            'review_due_at' => now()->addDays(30),
            'reminded_at' => null,
        ]);

        $this->assertNotNull($review->response_due_at);
        $this->assertNotNull($review->review_due_at);
        $this->assertNull($review->reminded_at);
    }

    public function test_review_overdue_scope(): void
    {
        // Create overdue review
        Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->subDays(5),
        ]);

        // Create non-overdue review
        Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->addDays(5),
        ]);

        // Create completed review (should not appear in overdue)
        Review::factory()->create([
            'status' => ReviewStatus::Completed,
            'review_due_at' => now()->subDays(5),
        ]);

        $overdue = Review::overdue()->get();

        $this->assertCount(1, $overdue);
        $this->assertEquals(ReviewStatus::Pending, $overdue->first()->status);
    }

    public function test_review_response_overdue_scope(): void
    {
        // Create response overdue review
        Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'response_due_at' => now()->subDays(2),
        ]);

        // Create non-overdue review
        Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'response_due_at' => now()->addDays(2),
        ]);

        // Create in_progress review (should not appear in response overdue)
        Review::factory()->create([
            'status' => ReviewStatus::InProgress,
            'response_due_at' => now()->subDays(2),
        ]);

        $overdue = Review::responseOverdue()->get();

        $this->assertCount(1, $overdue);
    }

    public function test_is_overdue_method(): void
    {
        $overdueReview = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->subDay(),
        ]);

        $notOverdueReview = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->addDay(),
        ]);

        $completedReview = Review::factory()->create([
            'status' => ReviewStatus::Completed,
            'review_due_at' => now()->subDay(),
        ]);

        $this->assertTrue($overdueReview->isOverdue());
        $this->assertFalse($notOverdueReview->isOverdue());
        $this->assertFalse($completedReview->isOverdue());
    }

    public function test_days_overdue_method(): void
    {
        $review = Review::factory()->create([
            'review_due_at' => now()->subDays(5),
        ]);

        $this->assertEquals(5, $review->daysOverdue());
    }

    public function test_days_until_review_due_method(): void
    {
        $review = Review::factory()->create([
            'review_due_at' => now()->addDays(10)->startOfDay(),
        ]);

        // Allow for small time differences during test execution
        $days = $review->daysUntilReviewDue();
        $this->assertGreaterThanOrEqual(9, $days);
        $this->assertLessThanOrEqual(10, $days);
    }

    public function test_deadline_status_method(): void
    {
        $overdue = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->subDay(),
        ]);

        $urgent = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->addDays(2),
        ]);

        $warning = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->addDays(5),
        ]);

        $normal = Review::factory()->create([
            'status' => ReviewStatus::Pending,
            'review_due_at' => now()->addDays(10),
        ]);

        $completed = Review::factory()->create([
            'status' => ReviewStatus::Completed,
            'review_due_at' => now()->subDay(),
        ]);

        $this->assertEquals('overdue', $overdue->deadlineStatus());
        $this->assertEquals('urgent', $urgent->deadlineStatus());
        $this->assertEquals('warning', $warning->deadlineStatus());
        $this->assertEquals('normal', $normal->deadlineStatus());
        $this->assertEquals('completed', $completed->deadlineStatus());
    }

    public function test_reviewer_can_accept_review(): void
    {
        $reviewer = User::where('email', 'reviewer@globalcampus.local')->first();
        $review = Review::factory()->create([
            'reviewer_id' => $reviewer->id,
            'status' => ReviewStatus::Pending,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->post(route('reviews.accept', $review));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $review->refresh();
        $this->assertEquals(ReviewStatus::InProgress, $review->status);
    }

    public function test_reviewer_can_decline_review(): void
    {
        $reviewer = User::where('email', 'reviewer@globalcampus.local')->first();
        $review = Review::factory()->create([
            'reviewer_id' => $reviewer->id,
            'status' => ReviewStatus::Pending,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->post(route('reviews.decline', $review));

        $response->assertRedirect();
        $response->assertSessionHas('info');

        $review->refresh();
        $this->assertEquals(ReviewStatus::Declined, $review->status);
    }

    public function test_reviewer_cannot_accept_completed_review(): void
    {
        $reviewer = User::where('email', 'reviewer@globalcampus.local')->first();
        $review = Review::factory()->create([
            'reviewer_id' => $reviewer->id,
            'status' => ReviewStatus::Completed,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->post(route('reviews.accept', $review));

        $response->assertForbidden();
    }

    public function test_reviewer_cannot_accept_another_users_review(): void
    {
        $reviewer = User::where('email', 'reviewer@globalcampus.local')->first();
        $otherReviewer = User::where('email', 'reviewer2@globalcampus.local')->first();
        $review = Review::factory()->create([
            'reviewer_id' => $otherReviewer->id,
            'status' => ReviewStatus::Pending,
        ]);

        $response = $this
            ->actingAs($reviewer)
            ->post(route('reviews.accept', $review));

        $response->assertForbidden();
    }

    public function test_deadlines_are_set_when_assigning_reviewer(): void
    {
        $sectionEditor = User::where('email', 'section@globalcampus.local')->first();
        $reviewer = User::where('email', 'reviewer2@globalcampus.local')->first();

        // Create a fresh article in submitted status
        $article = Article::factory()->create([
            'status' => ArticleStatus::Submitted,
            'editor_id' => $sectionEditor->id,
        ]);

        $response = $this
            ->actingAs($sectionEditor)
            ->post(route('editorial.assign-reviewer', $article), [
                'reviewer_id' => $reviewer->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $review = Review::where('article_id', $article->id)
            ->where('reviewer_id', $reviewer->id)
            ->first();

        $this->assertNotNull($review);
        $this->assertNotNull($review->response_due_at);
        $this->assertNotNull($review->review_due_at);
    }
}
