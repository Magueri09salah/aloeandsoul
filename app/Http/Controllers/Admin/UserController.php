<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AdminRole;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin accounts (owner only). Accounts are deactivated, never deleted, so
 * the audit trail keeps its authors.
 */
class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): Response
    {
        Gate::authorize(Permission::ManageUsers->value);

        return Inertia::render('admin/users/index', [
            'users' => User::query()->orderByDesc('is_active')->orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role->value,
                'role_label' => $u->role->label(),
                'is_active' => $u->is_active,
                'two_factor' => $u->hasTwoFactorEnabled(),
                'last_login_at' => $u->last_login_at?->toIso8601String(),
                'is_me' => $u->id === $request->user()->id,
            ]),
            'roles' => AdminRole::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize(Permission::ManageUsers->value);

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(AdminRole::class)],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        $user = User::query()->create([...$data, 'is_active' => true]);
        $this->audit->log('user.created', $user, ['email' => $user->email, 'role' => $user->role->value]);

        return back()->with('success', "Account created for {$user->name}. Share the password with them securely and ask them to change it.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize(Permission::ManageUsers->value);

        $data = $request->validate([
            'role' => ['required', Rule::enum(AdminRole::class)],
            'is_active' => ['boolean'],
        ]);
        $active = $request->boolean('is_active');
        $role = AdminRole::from($data['role']);

        // Never lock the shop out of its own admin.
        if ($user->role === AdminRole::Owner && ($role !== AdminRole::Owner || ! $active)) {
            $otherOwners = User::query()->where('role', AdminRole::Owner)->where('is_active', true)->whereKeyNot($user->id)->count();
            if ($otherOwners === 0) {
                return back()->with('error', 'There must always be at least one active owner.');
            }
        }

        $user->fill(['role' => $role, 'is_active' => $active]);
        $changes = AuditLogger::diff($user);
        DB::transaction(function () use ($user, $active) {
            $user->save();
            if (! $active) {
                // End every session of a deactivated account immediately.
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->forceFill(['remember_token' => null])->save();
            }
        });
        $this->audit->log('user.updated', $user, $changes);

        return back()->with('success', 'Account updated.');
    }
}
