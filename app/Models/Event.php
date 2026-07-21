<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Journal event content entity with title, date range,
 * type, and published status for the journal's events listing.
 */
#[Fillable(['title', 'description', 'event_date', 'event_end_date', 'event_type', 'location', 'url', 'is_published'])]
class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'event_end_date' => 'date',
            'is_published' => 'boolean',
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
