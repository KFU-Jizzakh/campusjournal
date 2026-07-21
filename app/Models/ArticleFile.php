<?php

namespace App\Models;

use App\Enums\ArticleFileLicense;
use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Supplementary file attached to an article with type,
 * visibility, licence, and optional thumbnail generation.
 *
 * SPECIFICATION: SPEC-07/AC-1, SPEC-07/AC-2, SPEC-07/BR-1, SPEC-07/BR-2, SPEC-07/BR-4
 */
#[Fillable(['article_id', 'file_path', 'original_name', 'file_type', 'visibility', 'license', 'language', 'file_size', 'mime_type', 'uploaded_by', 'disk'])]
class ArticleFile extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'file_type' => ArticleFileType::class,
            'visibility' => ArticleFileVisibility::class,
            'license' => ArticleFileLicense::class,
            'file_size' => 'integer',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function storage(): FilesystemAdapter
    {
        return Storage::disk($this->disk ?? 'public');
    }

    public function isPrivate(): bool
    {
        return ($this->disk ?? 'public') === 'local';
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->isPrivate()) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->isImage() || $this->isPrivate()) {
            return null;
        }

        $thumbnailPath = $this->getThumbnailPath();

        if (Storage::disk('public')->exists($thumbnailPath)) {
            return Storage::disk('public')->url($thumbnailPath);
        }

        return null;
    }

    public function isImage(): bool
    {
        return $this->file_type === ArticleFileType::Image
            || str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return $this->file_type === ArticleFileType::Video
            || str_starts_with($this->mime_type, 'video/');
    }

    public function isAudio(): bool
    {
        return $this->file_type === ArticleFileType::Audio
            || str_starts_with($this->mime_type, 'audio/');
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' bytes';
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    public function getThumbnailPath(): string
    {
        $directory = dirname($this->file_path);
        $filename = pathinfo($this->file_path, PATHINFO_FILENAME);

        return $directory.'/thumbnails/'.$filename.'.jpg';
    }

    public function deleteFile(): void
    {
        $this->storage()->delete($this->file_path);

        if ($this->isImage()) {
            $this->storage()->delete($this->getThumbnailPath());
        }
    }

    public static function diskForVisibility(ArticleFileVisibility $visibility): string
    {
        return $visibility === ArticleFileVisibility::Public ? 'public' : 'local';
    }

    public static function upload(Article $article, UploadedFile $file, array $meta, User $uploader): static
    {
        $visibility = ArticleFileVisibility::from($meta['visibility']);
        $disk = static::diskForVisibility($visibility);
        $filePath = $file->store("article_files/{$article->id}", $disk);

        try {
            return DB::transaction(function () use ($article, $file, $meta, $uploader, $filePath, $disk) {
                $articleFile = static::create([
                    'article_id' => $article->id,
                    'file_path' => $filePath,
                    'original_name' => basename($file->getClientOriginalName()),
                    'file_type' => $meta['file_type'],
                    'visibility' => $meta['visibility'],
                    'license' => $meta['license'] ?? null,
                    'language' => $meta['language'] ?? null,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by' => $uploader->id,
                    'disk' => $disk,
                ]);

                OutboxEvent::log('article_file.uploaded', $articleFile, [
                    'article_id' => $article->id,
                    'file_type' => $meta['file_type'],
                    'original_name' => $articleFile->original_name,
                ]);

                return $articleFile;
            });
        } catch (\Throwable $e) {
            Storage::disk($disk)->delete($filePath);
            throw $e;
        }
    }

    public function remove(): void
    {
        $fileData = [
            'article_id' => $this->article_id,
            'file_id' => $this->id,
            'original_name' => $this->original_name,
        ];
        $article = $this->article;

        DB::transaction(function () use ($article, $fileData) {
            $this->deleteFile();
            $this->delete();

            OutboxEvent::log('article_file.deleted', $article, $fileData);
        });
    }
}
