<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;

/**
 * PURPOSE: Accepts a signed coauthor invitation and links the author
 * record to the invitee's account (claim flow).
 */
class InvitationController extends Controller
{
    public function accept(Request $request, Author $author)
    {
        try {
            $author->claimFor($request->user());
        } catch (\DomainException $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', __('author.claim_success'));
    }
}
