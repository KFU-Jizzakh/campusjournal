<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('copyright_agreements', function (Blueprint $table) {
            $table->string('license')->nullable()->after('full_text');
        });
    }

    public function down(): void
    {
        Schema::table('copyright_agreements', function (Blueprint $table) {
            $table->dropColumn('license');
        });
    }
};
