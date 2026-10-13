<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Own account: name/email, password and two-factor authentication. The
 * updates themselves are handled by Fortify's endpoints under /admin/user.
 */
class ProfileController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $status = $request->session()->get('status');
        $raw = $user->getAttributes();
        $pending = ($raw['two_factor_secret'] ?? null) !== null && ($raw['two_factor_confirmed_at'] ?? null) === null;

        // Enabling 2FA requires a recent password confirmation (Fortify).
        $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);
        $passwordConfirmed = (time() - $confirmedAt) < (int) config('auth.password_timeout', 10800);

        return Inertia::render('admin/profile', [
            'user' => ['name' => $user->name, 'email' => $user->email],
            'twoFactor' => [
                'enabled' => $user->hasTwoFactorEnabled(),
                'pending' => $pending,
                // Rendered as an <img> data URI, so no SVG markup is injected into the page.
                'qr_code' => $pending ? 'data:image/svg+xml;base64,'.base64_encode($user->twoFactorQrCodeSvg()) : null,
                'setup_key' => $pending ? decrypt($user->two_factor_secret) : null,
                // Shown once, right after setup is confirmed or codes are regenerated.
                'recovery_codes' => in_array($status, ['two-factor-authentication-confirmed', 'recovery-codes-generated'], true)
                    ? $user->recoveryCodes()
                    : null,
            ],
            'passwordConfirmed' => $passwordConfirmed,
            'status' => $status,
        ]);
    }
}
