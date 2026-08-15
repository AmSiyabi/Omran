<?php

use App\Livewire\Admin\Security;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function fakeSessionRow(int $userId, string $id): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $userId,
        'ip_address' => '10.0.0.9',
        'user_agent' => 'Chrome/126 Windows',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->getTimestamp(),
    ]);
}

it('revokes one of the user own sessions', function () {
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole('owner');

    fakeSessionRow($user->id, 'other-session-id');

    Livewire::actingAs($user)
        ->test(Security::class)
        ->call('revoke', 'other-session-id');

    expect(DB::table('sessions')->where('id', 'other-session-id')->exists())->toBeFalse();
});

it('cannot revoke another user session — the query is scoped', function () {
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole('owner');

    $victim = User::factory()->create();
    fakeSessionRow($victim->id, 'victim-session-id');

    Livewire::actingAs($user)
        ->test(Security::class)
        ->call('revoke', 'victim-session-id');

    expect(DB::table('sessions')->where('id', 'victim-session-id')->exists())->toBeTrue();
});
