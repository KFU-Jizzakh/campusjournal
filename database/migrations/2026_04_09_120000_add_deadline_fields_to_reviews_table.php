<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('response_due_at')->nullable()->after('completed_at');
            $table->timestamp('review_due_at')->nullable()->after('response_due_at');
            $table->timestamp('reminded_at')->nullable()->after('review_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['response_due_at', 'review_due_at', 'reminded_at']);
        });
    }
};
