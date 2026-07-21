<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Journal issue with volume, number, year, DOI,
 * and published/planned status, containing published articles.
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
