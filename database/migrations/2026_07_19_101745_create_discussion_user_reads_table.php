<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discussion_user_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('discussion_id')->constrained('discussions')->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->unique(['user_id', 'discussion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_user_reads');
    }
};
