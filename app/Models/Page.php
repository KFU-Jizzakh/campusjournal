<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * PURPOSE: Static CMS page with slug-based routing for
 * content pages like About, For Authors, and Contacts.
 */
#[Fillable(['slug', 'title', 'body'])]
class Page extends Model
{
    use HasFactory, SoftDeletes;
}
