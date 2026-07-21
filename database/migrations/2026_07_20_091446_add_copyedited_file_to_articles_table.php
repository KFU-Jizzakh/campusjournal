<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('copyedited_file_path')->nullable()->after('copyedited_by');
            $table->timestamp('copyedited_file_uploaded_at')->nullable()->after('copyedited_file_path');
            $table->foreignId('copyedited_file_uploaded_by')->nullable()->after('copyedited_file_uploaded_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('copyedited_file_uploaded_by');
            $table->dropColumn('copyedited_file_uploaded_at');
            $table->dropColumn('copyedited_file_path');
        });
    }
};
