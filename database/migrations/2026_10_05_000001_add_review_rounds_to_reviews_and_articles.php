<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedSmallInteger('round')->default(1)->after('status');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedSmallInteger('current_round')->default(1)->after('status');
        });

        // Recreate the partial unique index scoped per round so a reviewer can be
        // re-invited in a later review round after completing/declining earlier ones.
        DB::statement('DROP INDEX IF EXISTS reviews_article_reviewer_active_unique');
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX reviews_article_reviewer_active_unique
            ON reviews (article_id, reviewer_id, round)
            WHERE status != 'declined'
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
            ON reviews (article_id, reviewer_id)
            WHERE status != 'declined'
        SQL);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('round');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('current_round');
        });
    }
};
