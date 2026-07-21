<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PURPOSE: Audit trail for Crossref DOI registration attempts,
 * tracking status, payload, and response for each deposit.
 *
 * SPECIFICATION: SPEC-08/AC-6, SPEC-08/BR-3
 */
#[Fillable(['article_id', 'doi', 'batch_id', 'xml_payload', 'status', 'http_status', 'response_body', 'error', 'attempted_by'])]
class CrossrefDeposit extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_FAILED = 'failed';

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function attemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attempted_by');
    }
}
