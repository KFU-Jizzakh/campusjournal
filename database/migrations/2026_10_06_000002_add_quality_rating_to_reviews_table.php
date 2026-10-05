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
            $table->unsignedSmallInteger('quality_rating')->nullable()->after('comments_for_author');
            $table->foreignId('rated_by')->nullable()->after('quality_rating')->constrained('users')->nullOnDelete();
            $table->timestamp('rated_at')->nullable()->after('rated_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rated_by');
            $table->dropColumn(['quality_rating', 'rated_at']);
        });
    }
};
