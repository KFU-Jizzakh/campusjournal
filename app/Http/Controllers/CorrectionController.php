<?php

namespace App\Http\Controllers;

use App\Models\Correction;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Serves the supporting PDF of a post-publication correction.
 * Corrections are public documents — access is gated by the parent
 * article being published, the file streams from the private local
 * disk (a raw Storage::url() there would 404/403 because the disk
 * requires signed URLs), and a missing file yields a plain 404.
 */
class CorrectionController extends Controller
{
    public function showFile(Correction $correction)
    {
        abort_unless($correction->file_path, 404);
        abort_unless($correction->article && $correction->article->isPublished(), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($correction->file_path), 404);

        return $disk->response($correction->file_path, basename($correction->file_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
