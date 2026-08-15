<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects unauthenticated requests on every admin route to login', function (string $route) {
    $this->get($route)->assertRedirect('/login');
})->with([
    '/admin',
    '/admin/security',
    '/admin/security/two-factor',
    '/admin/more',
    '/admin/courses',
    '/admin/finance',
]);

/**
 * Phase 1 acceptance: a Pest test per role asserting exactly which
 * routes are reachable (spec §14).
 */
dataset('role_matrix', [
    'owner' => ['owner', true, [
        '/admin' => 200,
        '/admin/security' => 200,
        '/admin/courses' => 200,
        '/admin/finance' => 200,
    ]],
    'admin' => ['admin', true, [
        '/admin' => 200,
        '/admin/security' => 200,
        '/admin/courses' => 200,
        '/admin/finance' => 200,
    ]],
    'coordinator' => ['coordinator', false, [
        '/admin' => 200,
        '/admin/security' => 200,
        '/admin/courses' => 200,
        '/admin/finance' => 403,
    ]],
    'viewer' => ['viewer', false, [
        '/admin' => 200,
        '/admin/security' => 200,
        '/admin/courses' => 200,
        '/admin/finance' => 403,
    ]],
]);

it('enforces the exact route matrix per role', function (string $role, bool $needsTwoFactor, array $expectations) {
    $factory = User::factory();

    if ($needsTwoFactor) {
        $factory = $factory->withConfirmedTwoFactor();
    }

    $user = $factory->create();
    $user->assignRole($role);

    foreach ($expectations as $route => $status) {
        $this->actingAs($user)->get($route)->assertStatus($status);
    }
})->with('role_matrix');

it('returns 403 on admin routes for a user with no role at all', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('has no public registration route', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

it('signs out a deactivated account on the next request', function () {
    $user = User::factory()->create(['is_active' => false]);
    $user->assignRole('coordinator');

    $this->actingAs($user)->get('/admin')->assertRedirect('/login');
    $this->assertGuest();
});
