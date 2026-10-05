<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PURPOSE: Author's point-by-point response to reviewers, submitted
 * alongside a revision resubmission and scoped to the new review round.
 *
 * SPECIFICATION: SPEC-25/AC-2, SPEC-25/BR-2
 */
#[Fillable(['article_id', 'round', 'body', 'file_path', 'uploaded_by'])]
class ResponseLetter extends Model
{
    protected function casts(): array
    {
        return [
            'round' => 'integer',
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

    public function roundLabel(): string
    {
        return __('dashboard.review_round_badge', ['round' => $this->round]);
    }
}
