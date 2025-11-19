<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->redirectToDashboard('Your email has already been verified.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->redirectToDashboard('Your account is verified and activated.');
    }

    private function redirectToDashboard(string $message): RedirectResponse
    {
        $dashboardUrl = route('dashboard', absolute: false).'?verified=1';

        return redirect()->to($dashboardUrl)->with('status', $message);
    }
}
