<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PURPOSE: Immutable record of an author's acceptance of a
 * copyright agreement version at the time of article submission,
 * preserving the version reference, timestamp, IP address, and
 * accepting user for audit trail.
 *
 * SPECIFICATION: SPEC-14/AC-3, SPEC-14/BR-2, SPEC-14/BR-3, SPEC-14/BR-4
 */
#[WithoutTimestamps]
#[Fillable(['article_id', 'copyright_agreement_id', 'accepted_by', 'accepted_ip'])]
class ArticleAgreement extends Model
{
    const UPDATED_AT = null;

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(CopyrightAgreement::class, 'copyright_agreement_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }
}
