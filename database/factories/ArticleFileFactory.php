<?php

namespace Database\Factories;

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * @extends Factory<ArticleFile>
 */
class ArticleFileFactory extends Factory
{
    protected $model = ArticleFile::class;

    public function definition(): array
    {
        $fileType = $this->faker->randomElement(ArticleFileType::cases());
        $visibility = $this->faker->randomElement(ArticleFileVisibility::cases());
        $license = $this->faker->optional(0.7)->randomElement(ArticleFileLicense::cases());
        $language = $this->faker->optional(0.5)->randomElement(['ru', 'en']);

        return [
            'article_id' => Article::factory(),
            'file_type' => $fileType,
            'visibility' => $visibility,
            'license' => $license,
            'language' => $language,
            'uploaded_by' => User::factory(),
            'disk' => ArticleFile::diskForVisibility($visibility),
        ];
    }

    /**
     * Create an image file with thumbnail
     */
    public function image(?Article $article = null, ?User $uploader = null): static
    {
        return $this->state(function (array $attributes) use ($article, $uploader) {
            $articleId = $article?->id ?? Article::factory()->create()->id;
            $fileName = 'image_'.$this->faker->unique()->word().'.jpg';
            $filePath = "article_files/{$articleId}/{$fileName}";

            // Create actual image file
            $this->createImageFile($filePath);

            return [
                'article_id' => $articleId,
                'file_path' => $filePath,
                'original_name' => $fileName,
                'file_type' => ArticleFileType::Image,
                'file_size' => $this->faker->numberBetween(50000, 500000),
                'mime_type' => 'image/jpeg',
                'uploaded_by' => $uploader?->id ?? User::factory()->create()->id,
                'visibility' => ArticleFileVisibility::Public,
                'disk' => 'public',
            ];
        });
    }

    /**
     * Create a document (PDF) file
     */
    public function document(?Article $article = null, ?User $uploader = null): static
    {
        return $this->state(function (array $attributes) use ($article, $uploader) {
            $articleId = $article?->id ?? Article::factory()->create()->id;
            $fileName = 'document_'.$this->faker->unique()->word().'.pdf';
            $filePath = "article_files/{$articleId}/{$fileName}";

            // Create actual PDF file
            $this->createPdfFile($filePath, $article);

            return [
                'article_id' => $articleId,
                'file_path' => $filePath,
                'original_name' => $fileName,
                'file_type' => ArticleFileType::Document,
                'file_size' => $this->faker->numberBetween(100000, 2000000),
                'mime_type' => 'application/pdf',
                'uploaded_by' => $uploader?->id ?? User::factory()->create()->id,
                'visibility' => ArticleFileVisibility::Public,
                'disk' => 'public',
            ];
        });
    }

    /**
     * Create a research data (CSV) file
     */
    public function researchData(?Article $article = null, ?User $uploader = null): static
    {
        return $this->state(function (array $attributes) use ($article, $uploader) {
            $articleId = $article?->id ?? Article::factory()->create()->id;
            $fileName = 'data_'.$this->faker->unique()->word().'.csv';
            $filePath = "article_files/{$articleId}/{$fileName}";

            // Create actual CSV file
            $this->createCsvFile($filePath);

            return [
                'article_id' => $articleId,
                'file_path' => $filePath,
                'original_name' => $fileName,
                'file_type' => ArticleFileType::ResearchData,
                'file_size' => $this->faker->numberBetween(1000, 100000),
                'mime_type' => 'text/csv',
                'uploaded_by' => $uploader?->id ?? User::factory()->create()->id,
                'visibility' => ArticleFileVisibility::Public,
                'disk' => 'public',
            ];
        });
    }

    /**
     * Set specific visibility
     */
    public function withVisibility(ArticleFileVisibility $visibility): static
    {
        $disk = ArticleFile::diskForVisibility($visibility);

        return $this->state([
            'visibility' => $visibility,
            'disk' => $disk,
        ])->afterCreating(function (ArticleFile $file) use ($disk) {
            if ($file->file_path && $file->disk !== 'public') {
                $public = Storage::disk('public');
                $target = Storage::disk($disk);
                if ($public->exists($file->file_path) && ! $target->exists($file->file_path)) {
                    $target->put($file->file_path, $public->get($file->file_path));
                    $thumbPath = $file->getThumbnailPath();
                    if ($public->exists($thumbPath)) {
                        $target->put($thumbPath, $public->get($thumbPath));
                    }
                }
            }
        });
    }

    /**
     * Create an actual image file with thumbnail
     */
    private function createImageFile(string $filePath): void
    {
        $fullPath = Storage::disk('public')->path($filePath);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            $manager = new ImageManager(Driver::class);
            $image = $manager->createImage(800, 600);

            // Add gradient-like background with random color
            $r = rand(100, 200);
            $g = rand(100, 200);
            $b = rand(100, 200);
            $image->fill("rgb({$r}, {$g}, {$b})");

            // Add some text
            $image->text('Sample Image', 100, 300, function ($font) {
                $font->size(30);
                $font->color('ffffff');
            });

            $image->save($fullPath, quality: 85);

            // Create thumbnail
            $thumbnailDir = $directory.'/thumbnails';
            if (! is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $filename = pathinfo($fullPath, PATHINFO_FILENAME);
            $thumbnailPath = $thumbnailDir.'/'.$filename.'.jpg';

            $thumbnail = $manager->decode($fullPath);
            $thumbnail->scaleDown(300, 300);
            $thumbnail->save($thumbnailPath, quality: 85);
        } catch (\Exception $e) {
            // If image creation fails, create an empty file
            file_put_contents($fullPath, '');
        }
    }

    /**
     * Create a minimal PDF file
     */
    private function createPdfFile(string $filePath, ?Article $article = null): void
    {
        $fullPath = Storage::disk('public')->path($filePath);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $title = $article?->title ?? 'Sample Document';
        $content = $this->generateFakePdfContent($title);
        file_put_contents($fullPath, $content);
    }

    /**
     * Create a CSV file with fake research data
     */
    private function createCsvFile(string $filePath): void
    {
        $fullPath = Storage::disk('public')->path($filePath);
        $directory = dirname($fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $headers = ['ID', 'Name', 'Value', 'Category', 'Date', 'Score'];
        $rows = [];

        for ($i = 1; $i <= 50; $i++) {
            $rows[] = [
                $i,
                $this->faker->word(),
                $this->faker->randomFloat(2, 0, 100),
                $this->faker->randomElement(['A', 'B', 'C', 'D']),
                $this->faker->date(),
                $this->faker->numberBetween(0, 100),
            ];
        }

        $csv = fopen($fullPath, 'w');
        fputcsv($csv, $headers);
        foreach ($rows as $row) {
            fputcsv($csv, $row);
        }
        fclose($csv);
    }

    /**
     * Generate minimal valid PDF content
     */
    private function generateFakePdfContent(string $title): string
    {
        $text = "Sample Document: {$title}\n\n";
        $text .= "This is a supplementary document for the article.\n\n";
        $text .= 'Generated: '.now()->format('Y-m-d H:i:s')."\n";
        $text .= "Pages: 1\n";

        $stream = $this->pdfEncodeStream($text);
        $streamLen = strlen($stream);

        $objects = [];
        $offsets = [];

        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj";
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj";
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj";
        $objects[4] = "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj";
        $objects[5] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj";

        $pdf = "%PDF-1.4\n";

        foreach ($objects as $num => $obj) {
            $offsets[$num] = strlen($pdf);
            $pdf .= $obj."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function pdfEncodeStream(string $text): string
    {
        $lines = explode("\n", $text);
        $commands = "BT\n/F1 11 Tf\n";
        $y = 800;

        foreach ($lines as $line) {
            if ($y < 40) {
                break;
            }
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $commands .= "1 0 0 1 40 {$y} Tm\n({$escaped}) Tj\n";
            $y -= 16;
        }

        $commands .= 'ET';

        return $commands;
    }
}
