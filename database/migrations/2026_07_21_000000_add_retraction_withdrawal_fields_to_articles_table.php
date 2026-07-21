<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->text('withdrawal_reason')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('retraction_reason')->nullable();
            $table->timestamp('retracted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'withdrawal_reason',
                'withdrawn_at',
                'retraction_reason',
                'retracted_at',
            ]);
        });
    }
};
