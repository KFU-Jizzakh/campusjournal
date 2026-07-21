<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->integer('volume')->default(1);
            $table->integer('number');
            $table->integer('year');
            $table->string('title');
            $table->string('theme')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->date('published_at')->nullable();
            $table->string('status')->default('planned'); // planned, in_progress, published
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
