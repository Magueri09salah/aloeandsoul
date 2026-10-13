<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

it('redirects guests away from every admin page', function (string $url) {
    $this->get($url)->assertRedirect('/admin/login');
})->with(['/admin', '/admin/orders', '/admin/products', '/admin/settings', '/admin/users', '/admin/audit-log']);

it('rejects guest mutations', function () {
    $this->post('/admin/products', ['name' => 'X'])->assertRedirect('/admin/login');
    $this->put('/admin/settings/store', [])->assertRedirect('/admin/login');
});

it('signs an admin in and out, regenerating the session', function () {
    $user = owner();

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'Correct-Horse-9'])->assertRedirect('/admin');
    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'auth.login')->exists())->toBeTrue();

    $this->post('/admin/logout')->assertRedirect('/admin/login');
    $this->assertGuest();
});

it('rejects wrong passwords and deactivated accounts with the same message', function () {
    $active = owner();
    $inactive = User::factory()->owner()->inactive()->create();

    $wrong = $this->post('/admin/login', ['email' => $active->email, 'password' => 'nope'])->assertSessionHasErrors('email');
    $off = $this->post('/admin/login', ['email' => $inactive->email, 'password' => 'Correct-Horse-9'])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe(trans('auth.failed'));
    $this->assertGuest();
});

it('throttles repeated login attempts', function () {
    $user = owner();

    foreach (range(1, 5) as $_) {
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'Correct-Horse-9'])->assertStatus(429);
    $this->assertGuest();
});

it('has no public registration', function () {
    $this->get('/admin/register')->assertNotFound();
    expect($this->post('/admin/register', ['email' => 'x@example.com'])->status())->toBeIn([404, 405]);
    $this->get('/register')->assertNotFound();
    expect(User::query()->count())->toBe(0);
});

it('signs out a user whose account was deactivated mid-session', function () {
    $user = staff();
    $this->actingAs($user)->get('/admin')->assertOk();

    $user->update(['is_active' => false]);

    $this->actingAs($user)->get('/admin')->assertRedirect('/admin/login');
});

it('enforces role permissions on pages and mutations', function () {
    $staff = staff();
    $manager = manager();

    $this->actingAs($staff)->get('/admin/orders')->assertOk();
    $this->actingAs($staff)->get('/admin/products')->assertOk();
    $this->actingAs($staff)->get('/admin/products/create')->assertForbidden();
    $this->actingAs($staff)->post('/admin/products', [])->assertForbidden();
    $this->actingAs($staff)->get('/admin/settings')->assertForbidden();
    $this->actingAs($staff)->get('/admin/coupons')->assertForbidden();

    $this->actingAs($manager)->get('/admin/products/create')->assertOk();
    $this->actingAs($manager)->get('/admin/settings')->assertForbidden();
    $this->actingAs($manager)->put('/admin/settings/store', ['name' => 'Hacked'])->assertForbidden();
    $this->actingAs($manager)->get('/admin/users')->assertForbidden();
    $this->actingAs($manager)->post('/admin/users', ['role' => 'owner'])->assertForbidden();
});

it('lets the owner add team members but never remove the last owner', function () {
    $owner = owner();

    $this->actingAs($owner)->post('/admin/users', [
        'name' => 'Helper',
        'email' => 'HELPER@example.com',
        'role' => 'staff',
        'password' => 'Another-Strong-Pass-7',
        'password_confirmation' => 'Another-Strong-Pass-7',
    ])->assertSessionHasNoErrors();
    expect(User::query()->where('email', 'helper@example.com')->value('role')->value)->toBe('staff');

    $this->actingAs($owner)->put("/admin/users/{$owner->id}", ['role' => 'staff', 'is_active' => true])->assertSessionHas('error');
    expect($owner->fresh()->role->value)->toBe('owner');
});

it('ends all sessions of a deactivated user', function () {
    $owner = owner();
    $staff = staff();
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $staff->id, 'payload' => '', 'last_activity' => time()]);

    $this->actingAs($owner)->put("/admin/users/{$staff->id}", ['role' => 'staff', 'is_active' => false])->assertSessionHasNoErrors();

    expect(DB::table('sessions')->where('user_id', $staff->id)->count())->toBe(0);
});

it('requires strong passwords for new admins', function () {
    $this->actingAs(owner())->post('/admin/users', [
        'name' => 'Weak', 'email' => 'weak@example.com', 'role' => 'staff',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('password');
});

it('marks admin responses as private and not indexable', function () {
    $this->actingAs(owner())->get('/admin')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('noindex,nofollow', false);
});
