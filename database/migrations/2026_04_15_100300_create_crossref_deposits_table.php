<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crossref_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('doi');
            $table->uuid('batch_id')->unique();
            $table->longText('xml_payload');
            $table->string('status')->default('pending'); // pending, submitted, accepted, failed
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('attempted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['article_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crossref_deposits');
    }
};
