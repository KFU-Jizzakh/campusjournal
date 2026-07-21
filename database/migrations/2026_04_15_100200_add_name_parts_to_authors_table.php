<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('full_name');
            $table->string('last_name')->nullable()->after('first_name');
        });

        DB::table('authors')->whereNotNull('full_name')->orderBy('id')->chunk(100, function ($authors) {
            foreach ($authors as $author) {
                [$first, $last] = self::splitName((string) $author->full_name);
                DB::table('authors')->where('id', $author->id)->update([
                    'first_name' => $first,
                    'last_name' => $last,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
    }

    private static function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName)) ?: [];

        if (count($parts) === 0) {
            return [null, null];
        }

        if (count($parts) === 1) {
            return [$parts[0], null];
        }

        $last = array_shift($parts);
        $first = implode(' ', $parts);

        return [$first, $last];
    }
};
