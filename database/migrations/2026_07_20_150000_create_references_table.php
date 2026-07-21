<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('references');

        Schema::create('references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->index()->constrained()->cascadeOnDelete();
            $table->text('raw');
            $table->string('doi')->nullable();
            $table->unsignedInteger('order')->default(1);
            $table->unsignedInteger('cited_count')->default(0);
            $table->timestamps();
        });

        DB::table('articles')
            ->whereNotNull('references_list')
            ->where('references_list', '!=', '')
            ->orderBy('id')
            ->chunk(500, function ($articles) {
                foreach ($articles as $article) {
                    $lines = preg_split('/\r\n|\r|\n/', trim($article->references_list));
                    $order = 1;

                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '') {
                            continue;
                        }

                        $doi = null;
                        if (preg_match('#\b(10\.\d{4,9}/[^\s,]+)#i', $line, $m)) {
                            $doi = rtrim($m[1], '.,;');
                        }

                        DB::table('references')->insert([
                            'article_id' => $article->id,
                            'raw' => $line,
                            'doi' => $doi,
                            'order' => $order++,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            });

        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn('references_list');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->text('references_list')->nullable()->after('body');
        });

        $references = DB::table('references')->orderBy('article_id')->orderBy('order')->get()->groupBy('article_id');

        foreach ($references as $articleId => $refs) {
            $lines = $refs->pluck('raw')->implode("\n");

            DB::table('articles')->where('id', $articleId)->update(['references_list' => $lines]);
        }

        Schema::dropIfExists('references');
    }
};
