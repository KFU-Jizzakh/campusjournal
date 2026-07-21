<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('abstract_ru')->nullable();
            $table->text('abstract_en')->nullable();
            $table->longText('body')->nullable();
            $table->string('doi')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, in_review, revision, copyediting, production, accepted, rejected, published
            $table->foreignId('issue_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete(); // Note: Articles remain when user is deleted, but lose submitter reference
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
