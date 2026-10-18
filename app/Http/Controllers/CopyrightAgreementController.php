<?php

namespace App\Http\Controllers;

use App\Models\CopyrightAgreement;

/**
 * PURPOSE: Serves the full text of a copyright agreement version
 * as a public page, linked from the submission form and article
 * detail views.
 */
class CopyrightAgreementController extends Controller
{
    public function show(CopyrightAgreement $agreement)
    {
        return view('agreements.show', compact('agreement'));
    }
}
