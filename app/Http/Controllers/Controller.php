<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * PURPOSE: Base controller providing authorization helpers
 * for all application controllers.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
