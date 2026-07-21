<?php

namespace App\Models;

use App\Enums\CorrectionType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PURPOSE: Post-publication correction notice linked to a
 * published article — corrigendum, erratum, or expression
 * of concern.
 *
 * SPECIFICATION: SPEC-16/AC-4, SPEC-16/BR-5, SPEC-16/BR-6
 */
#[Fillable(['article_id', 'type', 'title', 'description', 'file_path', 'published_at', 'created_by'])]
class Correction extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CorrectionType::class,
            'published_at' => 'datetime',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
