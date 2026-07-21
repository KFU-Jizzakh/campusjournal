<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->nullableMorphs('subject');
            $table->jsonb('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbox_events');
    }
};
