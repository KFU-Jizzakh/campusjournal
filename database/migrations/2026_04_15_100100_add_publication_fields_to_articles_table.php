<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('pages')->nullable()->after('keywords');
            $table->string('first_page')->nullable()->after('pages');
            $table->string('last_page')->nullable()->after('first_page');
            $table->timestamp('doi_registered_at')->nullable()->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['pages', 'first_page', 'last_page', 'doi_registered_at']);
        });
    }
};
