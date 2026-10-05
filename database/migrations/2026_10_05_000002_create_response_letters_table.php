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
        Schema::create('response_letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('round');
            $table->text('body');
            $table->string('file_path')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['article_id', 'round']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('response_letters');
    }
};
