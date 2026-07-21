<?php

namespace App\Http\Controllers;

use App\Models\EditorialBoardMember;

/**
 * PURPOSE: Serves the editorial board page with all members
 * sorted by order and grouped by role.
 */
class EditorialBoardController extends Controller
{
    public function index()
    {
        $members = EditorialBoardMember::with(['author'])
            ->orderBy('sort_order')
            ->get();

        return view('editorial-board.index', compact('members'));
    }
}
