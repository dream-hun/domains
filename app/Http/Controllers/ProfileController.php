<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UpdateBillingRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Country;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

final class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $countries = Country::query()->select(['iso_code', 'name'])->get();

        return view('profile.edit', [
            'user' => $request->user(), 'countries' => $countries,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['name'])) {
            $name = mb_trim((string) $data['name']);
            unset($data['name']);

            if ($name !== '') {
                $parts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);
                $first = array_shift($parts) ?? $request->user()->first_name;
                $last = $parts !== [] ? implode(' ', $parts) : ($request->user()->last_name ?? '');

                $data['first_name'] ??= $first;
                $data['last_name'] ??= $last !== '' ? $last : null;
            }
        }

        if (! isset($data['first_name'])) {
            $data['first_name'] = $request->user()->first_name;
        }

        if (! isset($data['last_name'])) {
            $data['last_name'] = $request->user()->last_name;
        }

        $request->user()->fill($data);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit')->with('profile_status', $request->user()->first_name.' your profile has been updated.');
    }

    /**
     * Update Billing Information
     */
    public function updateAddress(UpdateBillingRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();

            Auth::user()->address()->updateOrCreate(
                ['user_id' => Auth::id()],
                $validated
            );

            return to_route('profile.edit')->with('billing_status', 'success')->with('billing_message', $request->user()->first_name.', your billing information has been updated successfully.');
        } catch (Exception $e) {
            return to_route('profile.edit')->with('billing_status', 'error')->with('billing_message', 'Failed to update billing information. Please try again.');
        }
    }

    /**
     * Delete the user's account.
     */
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
