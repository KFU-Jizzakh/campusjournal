<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->timestamp('copyedited_at')->nullable()->after('decided_by');
            $table->foreignId('copyedited_by')->nullable()->after('copyedited_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('production_at')->nullable()->after('copyedited_by');
            $table->foreignId('production_by')->nullable()->after('production_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('production_by');
            $table->dropColumn('production_at');
            $table->dropConstrainedForeignId('copyedited_by');
            $table->dropColumn('copyedited_at');
        });
    }
};
