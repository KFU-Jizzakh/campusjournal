<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Editorial board member linking an Author to a role
 * with sort order for the journal's editorial board page.
 */
#[Fillable(['author_id', 'role', 'sort_order'])]
class EditorialBoardMember extends Model
{
    use HasFactory, SoftDeletes;

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
