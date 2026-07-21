<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PURPOSE: Append-only record of each galley revision request
 * made by the author, capturing who requested, the comment,
 * and when.
 *
 * SPECIFICATION: SPEC-13/BR-3
 */
#[Fillable(['article_id', 'requested_by', 'comment'])]
#[WithoutTimestamps]
class GalleyRevision extends Model
{
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
