<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Article rubric/category with name, slug, and sort order
 * for organising articles by topic.
 */
#[Fillable(['name', 'slug', 'description', 'sort_order'])]
class Category extends Model
{
    use HasFactory, SoftDeletes;

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
