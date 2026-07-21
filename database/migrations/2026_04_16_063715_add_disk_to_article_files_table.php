<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('article_files', function (Blueprint $table) {
            $table->string('disk', 20)->default('public')->after('uploaded_by');
        });
    }

    public function down(): void
    {
        Schema::table('article_files', function (Blueprint $table) {
            $table->dropColumn('disk');
        });
    }
};
