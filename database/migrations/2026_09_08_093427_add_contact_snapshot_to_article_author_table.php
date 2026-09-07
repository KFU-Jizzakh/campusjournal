<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot author contact details (email, phone, country, city, website)
     * onto the article_author pivot so each article keeps the contact
     * data as submitted, independent of later submissions that may
     * rewrite the shared authors row.
     */
    public function up(): void
    {
        Schema::table('article_author', function (Blueprint $table) {
            $table->string('email')->nullable()->after('order');
            $table->string('phone')->nullable()->after('email');
            $table->string('country')->nullable()->after('phone');
            $table->string('city')->nullable()->after('country');
            $table->string('website')->nullable()->after('city');
        });

        DB::statement(<<<'SQL'
            update article_author as aa
            set email = a.email, phone = a.phone, country = a.country,
                city = a.city, website = a.website
            from authors as a
            where a.id = aa.author_id
            SQL);
    }

    public function down(): void
    {
        Schema::table('article_author', function (Blueprint $table) {
            $table->dropColumn(['email', 'phone', 'country', 'city', 'website']);
        });
    }
};
