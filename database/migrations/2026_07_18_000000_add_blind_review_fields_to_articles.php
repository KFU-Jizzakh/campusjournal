<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('review_type')->default('single_blind')->after('status');
            $table->string('blinded_pdf_path')->nullable()->after('pdf_path');
            $table->timestamp('blinded_at')->nullable()->after('blinded_pdf_path');
            $table->foreignId('blinded_by')->nullable()->after('blinded_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('blinded_by');
            $table->dropColumn(['review_type', 'blinded_pdf_path', 'blinded_at']);
        });
    }
};
