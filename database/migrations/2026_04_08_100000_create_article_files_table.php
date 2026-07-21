<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('file_type'); // ArticleFileType enum
            $table->string('visibility'); // ArticleFileVisibility enum
            $table->string('license')->nullable(); // ArticleFileLicense enum
            $table->string('language', 10)->nullable();
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['article_id', 'visibility']);
            $table->index('file_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_files');
    }
};
