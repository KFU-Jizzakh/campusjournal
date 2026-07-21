<?php

namespace App\Http\Controllers;

use App\Enums\Country;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

/**
 * PURPOSE: Handles user profile editing, password changes,
 * and notification preference management.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user()->load('profile'),
            'countries' => Country::cases(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->email = $data['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->notification_preferences = [
            'status_changes_enabled' => $request->boolean('notification_status_changes', true),
            'email_status_changes' => $request->boolean('notification_email_status', true),
            'email_discussions' => $request->boolean('notification_email_discussions', true),
            'site_discussions' => $request->boolean('notification_site_discussions', true),
        ];

        $user->save();

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'last_name' => $data['last_name'],
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'affiliation' => $data['affiliation'] ?? null,
                'country' => $data['country'] ?? null,
                'orcid' => $data['orcid'] ?? null,
                'url' => $data['url'] ?? null,
                'phone' => $data['phone'] ?? null,
                'bio' => $data['bio'] ?? null,
            ]
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
