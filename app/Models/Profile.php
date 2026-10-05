<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: User display profile with full name, affiliation,
 * ORCID, and contact details.
 */
#[Fillable(['user_id', 'last_name', 'first_name', 'middle_name', 'affiliation', 'country', 'orcid', 'url', 'phone', 'bio', 'interests', 'signature'])]
class Profile extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'interests' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }
}
