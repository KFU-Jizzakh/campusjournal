<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PURPOSE: Backfill query indexes the schema grew without — foreign
 * keys PostgreSQL does not index automatically (hot relation lookups
 * like $article->reviews or User::with('profile) ran as seq scans),
 * pivot columns covered only by composite uniques (article_author by
 * (article_id, author_id) is useless for a reverse lookup by author),
 * the public published_at ordering, and a partial unique DOI guard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('article_id', 'reviews_article_id_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->index('user_id', 'profiles_user_id_index');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->index('user_id', 'authors_user_id_index');
        });

        Schema::table('article_author', function (Blueprint $table) {
            $table->index('author_id', 'article_author_author_id_index');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->index('issue_id', 'articles_issue_id_index');
            $table->index('category_id', 'articles_category_id_index');
            $table->index('published_at', 'articles_published_at_index');
        });

        Schema::table('discussions', function (Blueprint $table) {
            $table->index('user_id', 'discussions_user_id_index');
        });

        Schema::table('article_agreements', function (Blueprint $table) {
            $table->index('article_id', 'article_agreements_article_id_index');
        });

        Schema::table('galley_revisions', function (Blueprint $table) {
            $table->index('article_id', 'galley_revisions_article_id_index');
        });

        Schema::table('discussion_user_reads', function (Blueprint $table) {
            $table->index('discussion_id', 'discussion_user_reads_discussion_id_index');
        });

        DB::statement('CREATE UNIQUE INDEX articles_doi_unique ON articles (doi) WHERE doi IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS articles_doi_unique');

        Schema::table('discussion_user_reads', function (Blueprint $table) {
            $table->dropIndex('discussion_user_reads_discussion_id_index');
        });

        Schema::table('galley_revisions', function (Blueprint $table) {
            $table->dropIndex('galley_revisions_article_id_index');
        });

        Schema::table('article_agreements', function (Blueprint $table) {
            $table->dropIndex('article_agreements_article_id_index');
        });

        Schema::table('discussions', function (Blueprint $table) {
            $table->dropIndex('discussions_user_id_index');
        });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('articles_published_at_index');
            $table->dropIndex('articles_category_id_index');
            $table->dropIndex('articles_issue_id_index');
        });

        Schema::table('article_author', function (Blueprint $table) {
            $table->dropIndex('article_author_author_id_index');
        });

        Schema::table('authors', function (Blueprint $table) {
            $table->dropIndex('authors_user_id_index');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_user_id_index');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_article_id_index');
        });
    }
};
