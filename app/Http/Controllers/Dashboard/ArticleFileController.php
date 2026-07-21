<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\ArticleFileType;
use App\Enums\ArticleFileVisibility;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleFileRequest;
use App\Models\Article;
use App\Models\ArticleFile;
use App\Policies\ArticleFilePolicy;
use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * PURPOSE: Handles upload, download, and deletion of
 * supplementary files with type, visibility, and license
 * metadata; generates thumbnails for images.
 *
 * SPECIFICATION: SPEC-07/AC-1, SPEC-07/AC-2, SPEC-07/AC-6, SPEC-07/AC-7
 */
class ArticleFileController extends Controller
{
    public function store(StoreArticleFileRequest $request, Article $article)
    {
        $validated = $request->validated();
        $uploadedFile = $request->file('file');

        $articleFile = ArticleFile::upload($article, $uploadedFile, $validated, $request->user());

        // Create thumbnail for public images only (private images have no direct URL)
        $fileType = ArticleFileType::from($validated['file_type']);
        $visibility = ArticleFileVisibility::from($validated['visibility']);
        if ($fileType === ArticleFileType::Image && $visibility === ArticleFileVisibility::Public) {
            try {
                $this->createThumbnail($articleFile);
            } catch (\Throwable $e) {
                report($e);

                return redirect()->back()
                    ->with('success', 'Файл успешно загружен.')
                    ->with('warning', 'Не удалось создать миниатюру: '.$e->getMessage());
            }
        }

        return redirect()->back()
            ->with('success', 'Файл успешно загружен.');
    }

    public function destroy(Request $request, ArticleFile $file)
    {
        $this->authorize('delete', $file);

        $file->remove();

        return redirect()->back()
            ->with('success', 'Файл удален.');
    }

    public function download(ArticleFile $file)
    {
        $file->loadMissing('article');

        $user = auth()->user();
        $policy = app(ArticleFilePolicy::class);

        abort_unless($policy->download($user, $file), 404);

        $storage = $file->storage();

        if (! $storage->exists($file->file_path)) {
            abort(404, 'Файл не найден.');
        }

        return $storage->download(
            $file->file_path,
            $file->original_name
        );
    }

    private function createThumbnail(ArticleFile $articleFile): void
    {
        $storage = $articleFile->storage();
        $fullPath = $storage->path($articleFile->file_path);
        $directory = dirname($fullPath);
        $filename = pathinfo($fullPath, PATHINFO_FILENAME);
        $thumbnailDir = $directory.'/thumbnails';

        if (! is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $thumbnailPath = $thumbnailDir.'/'.$filename.'.jpg';

        $manager = new ImageManager(Driver::class);
        $image = $manager->decode($fullPath);
        $image->scaleDown(300, 300);
        $image->save($thumbnailPath, quality: 85);
    }
}
