<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('galley_pdf_path')->nullable()->after('production_by');
            $table->timestamp('galley_uploaded_at')->nullable()->after('galley_pdf_path');
            $table->foreignId('galley_uploaded_by')->nullable()->after('galley_uploaded_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('galley_sent_at')->nullable()->after('galley_uploaded_by');
            $table->foreignId('galley_sent_by')->nullable()->after('galley_sent_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('galley_approved_at')->nullable()->after('galley_sent_by');
            $table->foreignId('galley_approved_by')->nullable()->after('galley_approved_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropForeign(['galley_uploaded_by']);
            $table->dropForeign(['galley_sent_by']);
            $table->dropForeign(['galley_approved_by']);
            $table->dropColumn([
                'galley_pdf_path',
                'galley_uploaded_at',
                'galley_uploaded_by',
                'galley_sent_at',
                'galley_sent_by',
                'galley_approved_at',
                'galley_approved_by',
            ]);
        });
    }
};
