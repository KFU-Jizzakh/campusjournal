<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ResponseLetter;
use Illuminate\Support\Facades\Storage;

/**
 * PURPOSE: Serves author response-letter attachments to editors,
 * the author, and reviewers of the corresponding round.
 *
 * SPECIFICATION: SPEC-25/AC-2, SPEC-25/BR-4
 */
class ResponseLetterController extends Controller
{
    public function showFile(ResponseLetter $responseLetter)
    {
        $this->authorize('view', $responseLetter);

        abort_unless($responseLetter->file_path, 404);

        return Storage::disk('local')->download($responseLetter->file_path);
    }
}
