<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            if (! $this->indexExists('articles', 'status')) {
                $table->index('status');
            }
            if (! $this->indexExists('articles', 'submitted_by')) {
                $table->index('submitted_by');
            }
            // Note: editor_id index is added in 2026_03_29_100000_add_editorial_workflow_to_articles.php
        });

        Schema::table('issues', function (Blueprint $table) {
            if (! $this->indexExists('issues', 'status')) {
                $table->index('status');
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            if (! $this->indexExists('reviews', 'status')) {
                $table->index('status');
            }
            if (! $this->indexExists('reviews', 'reviewer_id')) {
                $table->index('reviewer_id');
            }
        });

        Schema::table('events', function (Blueprint $table) {
            if (! $this->indexExists('events', 'is_published')) {
                $table->index('is_published');
            }
            if (! $this->indexExists('events', 'event_date')) {
                $table->index('event_date');
            }
        });

        Schema::table('news', function (Blueprint $table) {
            if (! $this->indexExists('news', 'is_published')) {
                $table->index('is_published');
            }
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndexIfExists(['status']);
            $table->dropIndexIfExists(['submitted_by']);
            // Note: editor_id index is dropped in 2026_03_29_100000_add_editorial_workflow_to_articles.php
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->dropIndexIfExists(['status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndexIfExists(['status']);
            $table->dropIndexIfExists(['reviewer_id']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropIndexIfExists(['is_published']);
            $table->dropIndexIfExists(['event_date']);
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropIndexIfExists(['is_published']);
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $column): bool
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $result = DB::select(
                "SELECT name FROM sqlite_master WHERE type='index' AND tbl_name = ? AND sql LIKE ?",
                [$table, '%'.$column.'%']
            );

            return count($result) > 0;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $result = DB::select(
                "SHOW INDEX FROM {$table} WHERE Column_name = ?",
                [$column]
            );

            return count($result) > 0;
        }

        if ($driver === 'pgsql') {
            $result = DB::select(
                'SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexdef LIKE ?',
                [$table, '%'.$column.'%']
            );

            return count($result) > 0;
        }

        return false;
    }
};
