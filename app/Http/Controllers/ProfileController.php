<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();
 
        $user->delete();
 
        $request->session()->invalidate();
        $request->session()->regenerateToken();
 
        return Redirect::to('/');
    }

    /**
     * Update the user's signature file.
     */
    public function updateSignature(Request $request): RedirectResponse
    {
        $request->validate([
            'signature' => 'required|image|max:2048', // max 2MB
        ]);

        $user = $request->user();

        if ($request->hasFile('signature')) {
            if ($user->signature_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->signature_path);
            }
            $path = $request->file('signature')->store('signatures', 'public');
            $user->update([
                'signature_path' => $path
            ]);
        }

        return Redirect::route('profile.edit')->with('status', 'signature-updated');
    }

    /**
     * Delete the user's signature file.
     */
    public function destroySignature(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->signature_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->signature_path);
            $user->update([
                'signature_path' => null
            ]);
        }
        return Redirect::route('profile.edit')->with('status', 'signature-deleted');
    }
}
