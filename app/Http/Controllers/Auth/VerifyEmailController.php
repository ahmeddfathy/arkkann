<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class VerifyEmailController extends Controller
{
    /**
     * Generate a secure hash for email verification.
     *
     * @param string $email
     * @return string
     */
    public static function generateVerificationHash(string $email): string
    {
        return hash_hmac('sha256', $email, config('app.key'));
    }

    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();
        $hash = self::generateVerificationHash($user->email);

        if ($request->route('hash') !== $hash) {
            return redirect()->route('verification.notice')->with('error', 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
