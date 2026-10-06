<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Author;
use Illuminate\Http\Request;

/**
 * PURPOSE: Accepts a signed coauthor invitation and links the author
 * record to the invitee's account (claim flow). The signed URL binds
 * the claim to the article that issued the invitation.
 */
class InvitationController extends Controller
{
    public function accept(Request $request, Article $article, Author $author)
    {
        try {
            $author->claimFor($request->user(), $article);
        } catch (\DomainException $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', __('author.claim_success'));
    }
}
