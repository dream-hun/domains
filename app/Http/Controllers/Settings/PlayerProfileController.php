<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\PlayerProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final class PlayerProfileController extends Controller
{
    public function update(PlayerProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        /** @var array<string, mixed> $data */
        $data = $request->safe()->except('profile_image');

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        $user->profile()->updateOrCreate(
            ['player_id' => $user->id],
            $data
        );

        return to_route('profile.edit')->with('status', 'player-profile-updated');
    }
}
