<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Partner organisation content entity with name, logo,
 * URL, and sort order for the journal's homepage.
 */
#[Fillable(['name', 'description', 'logo_path', 'url', 'sort_order'])]
class Organization extends Model
{
    use HasFactory, SoftDeletes;
}
