<?php

namespace App\Console\Commands;

use App\Enums\AdminRole;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * The only way to create the first administrator. The password is typed
 * interactively (never passed as an argument, so it stays out of shell history).
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {--role=owner : owner, manager or staff}';

    protected $description = 'Create an admin account (prompts for name, email and password)';

    public function handle(AuditLogger $audit): int
    {
        $name = text('Name', required: true, validate: ['name' => 'max:120']);
        $email = mb_strtolower(text('Email', required: true, validate: ['email' => ['email:rfc', 'max:255', Rule::unique('users', 'email')]]));
        $role = AdminRole::tryFrom((string) $this->option('role'))
            ?? AdminRole::from(select('Role', array_combine(
                array_map(fn (AdminRole $r) => $r->value, AdminRole::cases()),
                array_map(fn (AdminRole $r) => $r->label().' — '.$r->description(), AdminRole::cases()),
            ), default: 'owner'));

        $password = password('Password (at least 12 characters, mixed case and a number)', required: true);
        $confirm = password('Confirm password', required: true);

        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirm],
            ['password' => ['required', Password::defaults(), 'confirmed']],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => true,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();
        $audit->log('user.created', $user, ['email' => $email, 'role' => $role->value, 'via' => 'console']);

        $this->info("{$role->label()} account created for {$email}. Sign in at ".url('/admin/login'));
        $this->line('Next: turn on two-factor authentication under Profile.');

        return self::SUCCESS;
    }
}
