<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('locks an owner without confirmed 2FA out of every admin route except 2FA setup', function (string $route) {
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    $this->actingAs($owner)
        ->get($route)
        ->assertRedirect(route('admin.security.two-factor'));
})->with([
    '/admin',
    '/admin/security',
    '/admin/more',
    '/admin/courses',
    '/admin/finance',
]);

it('lets an owner without confirmed 2FA reach the 2FA setup page', function () {
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    $this->actingAs($owner)
        ->get(route('admin.security.two-factor'))
        ->assertOk();
});

it('lets an owner with confirmed 2FA reach the dashboard', function () {
    $owner = User::factory()->withConfirmedTwoFactor()->create();
    $owner->assignRole('owner');

    $this->actingAs($owner)->get('/admin')->assertOk();
});

it('does not force 2FA on roles where it is optional', function (string $role) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)->get('/admin')->assertOk();
})->with(['coordinator', 'viewer']);
