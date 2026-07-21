<?php

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Services\Jats\JatsXmlBuilder;
use Illuminate\Support\Facades\Storage;

test('uploaded JATS XML file overrides generator', function () {
    Storage::fake('public');

    $article = Article::factory()->published()->create(['title' => 'Generated Title']);

    $custom = '<?xml version="1.0"?><article><front><article-title>Custom Override</article-title></front></article>';
    Storage::disk('public')->put("article_files/{$article->id}/override.xml", $custom);

    ArticleFile::create([
        'article_id' => $article->id,
        'file_path' => "article_files/{$article->id}/override.xml",
        'original_name' => 'override.xml',
        'file_type' => ArticleFileType::JatsXml,
        'visibility' => ArticleFileVisibility::Public,
        'license' => ArticleFileLicense::CcBy,
        'file_size' => strlen($custom),
        'mime_type' => 'application/xml',
    ]);

    $xml = app(JatsXmlBuilder::class)->build($article->fresh());

    expect($xml)->toBe($custom);
    expect($xml)->not->toContain('Generated Title');
});
