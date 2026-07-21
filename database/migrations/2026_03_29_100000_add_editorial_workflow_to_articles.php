<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('editor_id')->nullable()->after('submitted_by')->constrained('users')->nullOnDelete();
            $table->string('decision')->nullable()->after('status');
            $table->text('decision_comments')->nullable()->after('decision');
            $table->timestamp('decided_at')->nullable()->after('decision_comments');
            $table->foreignId('decided_by')->nullable()->after('decided_at')->constrained('users')->nullOnDelete();

            // Add index for editor_id after the column is created
            $table->index('editor_id');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['editor_id']);
            $table->dropConstrainedForeignId('editor_id');
            $table->dropColumn(['decision', 'decision_comments', 'decided_at']);
            $table->dropConstrainedForeignId('decided_by');
        });
    }
};
