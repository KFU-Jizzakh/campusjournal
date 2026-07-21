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
        // Add a partial unique index to prevent duplicate active reviewers
        // A reviewer can only have one non-declined review per article
        // Declined reviewers can be re-assigned
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX reviews_article_reviewer_active_unique 
            ON reviews (article_id, reviewer_id) 
            WHERE status != 'declined'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS reviews_article_reviewer_active_unique');
    }
};
