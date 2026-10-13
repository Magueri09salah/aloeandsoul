<?php

namespace App\Providers;

use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

/**
 * Admin authentication (Fortify, headless) rendered with Inertia pages.
 * Registration is disabled: admins are created with `php artisan admin:create`
 * or by an owner in Admin → Users.
 */
class FortifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Inactive accounts cannot sign in. Same response as a wrong password
        // so account status is not disclosed.
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::query()->where('email', mb_strtolower((string) $request->input('email')))->first();

            if ($user && $user->is_active && Hash::check((string) $request->input('password'), $user->password)) {
                return $user;
            }

            return null;
        });

        Fortify::loginView(fn (Request $request) => Inertia::render('admin/auth/login', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('admin/auth/forgot-password', [
            'status' => $request->session()->get('status'),
        ]));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('admin/auth/reset-password', [
            'email' => (string) $request->query('email'),
            'token' => (string) $request->route('token'),
        ]));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('admin/auth/two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => Inertia::render('admin/auth/confirm-password'));
    }
}
