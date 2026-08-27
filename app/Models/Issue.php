<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Exceptions\IssueUnpublishFailedException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Journal issue with volume, number, year, DOI,
 * and published/planned status, containing published articles.
 *
 * SPECIFICATION: SPEC-24/BR-1, SPEC-24/BR-2
 */
#[Fillable(['volume', 'number', 'year', 'title', 'theme', 'description', 'cover_path', 'pdf_path', 'doi', 'published_at', 'status'])]
class Issue extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'published_at' => 'date',
        ];
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * PURPOSE: Guard the public visibility invariant — an issue
     * cannot lose its published status while it still contains
     * published articles.
     *
     * SPECIFICATION: SPEC-24/BR-1
     */
    protected static function booted(): void
    {
        static::updating(function (self $issue) {
            if (
                $issue->isDirty('status')
                && $issue->status !== 'published'
                && $issue->getOriginal('status') === 'published'
                && $issue->hasPublishedArticles()
            ) {
                throw new IssueUnpublishFailedException;
            }
        });
    }

    /**
     * PURPOSE: Tells whether the issue is publicly visible.
     *
     * SPECIFICATION: SPEC-24/BR-2
     */
    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /**
     * PURPOSE: Tells whether the issue still contains published
     * or retracted articles (SPEC-24/BR-1 treats them equally).
     *
     * SPECIFICATION: SPEC-24/BR-1
     */
    public function hasPublishedArticles(): bool
    {
        return $this->articles()
            ->whereIn('status', [ArticleStatus::Published, ArticleStatus::Retracted])
            ->exists();
    }

    public function getFullTitleAttribute(): string
    {
        $parts = array_filter([
            $this->volume ? "Том {$this->volume}" : null,
            $this->number ? "№{$this->number}" : null,
        ]);

        $label = implode(', ', $parts);

        if ($this->year) {
            $label .= $label ? " ({$this->year})" : $this->year;
        }

        if ($this->title) {
            $label .= $label ? " — {$this->title}" : $this->title;
        }

        return $label ?: 'Выпуск';
    }
}
