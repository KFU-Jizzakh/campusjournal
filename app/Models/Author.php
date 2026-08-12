<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Article authorship metadata with ORCID, SPIN, degree,
 * and organisation, linked to User and pivotable to Articles.
 */
#[Fillable(['user_id', 'full_name', 'first_name', 'last_name', 'degree', 'position', 'organization', 'bio', 'photo_path', 'orcid', 'email', 'spin_code', 'author_id_elibrary', 'website'])]
class Author extends Model
{
    use HasFactory, SoftDeletes;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_author')
            ->withPivot('order')
            ->orderByPivot('order');
    }

    public function getNamePartsAttribute(): array
    {
        $first = $this->first_name;
        $last = $this->last_name;

        if (! filled($first) && ! filled($last) && filled($this->full_name)) {
            $parts = preg_split('/\s+/u', trim((string) $this->full_name)) ?: [];
            if (count($parts) >= 2) {
                $last = array_shift($parts);
                $first = implode(' ', $parts);
            } else {
                $last = $parts[0] ?? null;
            }
        }

        return [
            'given_name' => $first ? mb_trim((string) $first) : null,
            'surname' => $last ? mb_trim((string) $last) : (string) $this->full_name,
        ];
    }

    public function getGostNameAttribute(): string
    {
        $parts = explode(' ', $this->full_name);

        if (count($parts) >= 3) {
            return $parts[0].' '.mb_substr($parts[1], 0, 1).'.'.mb_substr($parts[2], 0, 1).'.';
        }

        if (count($parts) === 2) {
            return $parts[0].' '.mb_substr($parts[1], 0, 1).'.';
        }

        return $this->full_name;
    }
}
