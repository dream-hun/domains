<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException|Exception
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'client_code' => User::generateCustomerNumber(),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Fire the Registered event so Laravel's SendEmailVerificationNotification listener runs
        event(new Registered($user));

        // Log the user in to match typical verification flow
        Auth::login($user);

        return redirect()->route('verification.notice')->with('success', 'An activation email has been sent to your email address.');
    }
}
