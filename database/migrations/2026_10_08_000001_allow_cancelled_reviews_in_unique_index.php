<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Cancelled assignments free the (article, reviewer, round) slot so
        // the editor can re-invite — same semantics as declined reviews.
        DB::statement('DROP INDEX IF EXISTS reviews_article_reviewer_active_unique');
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX reviews_article_reviewer_active_unique
            ON reviews (article_id, reviewer_id, round)
            WHERE status NOT IN ('declined', 'cancelled')
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS reviews_article_reviewer_active_unique');
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX reviews_article_reviewer_active_unique
            ON reviews (article_id, reviewer_id, round)
            WHERE status != 'declined'
        SQL);
    }
};
