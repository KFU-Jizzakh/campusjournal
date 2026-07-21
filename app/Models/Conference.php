<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Conference content entity with title, date range,
 * location, and published status for the journal's conference listings.
 */
#[Fillable(['title', 'slug', 'description', 'body', 'event_date', 'event_end_date', 'location', 'url', 'is_published', 'published_at'])]
class Conference extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'event_end_date' => 'date',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('event_date', '>=', now());
    }
}
