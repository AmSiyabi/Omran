<?php

use App\Livewire\Admin\TwoFactorSetup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('rejects enabling 2FA with a wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password-123')]);
    $user->assignRole('owner');

    Livewire::actingAs($user)
        ->test(TwoFactorSetup::class)
        ->set('password', 'not-the-password')
        ->call('enable')
        ->assertHasErrors('password');

    expect($user->refresh()->two_factor_secret)->toBeNull();
});

it('enables, confirms with a real TOTP code, and reveals recovery codes once', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password-123')]);
    $user->assignRole('owner');

    $component = Livewire::actingAs($user)
        ->test(TwoFactorSetup::class)
        ->set('password', 'correct-password-123')
        ->call('enable')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->hasConfirmedTwoFactor())->toBeFalse();

    $validCode = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));

    $component->set('code', $validCode)
        ->call('confirm')
        ->assertHasNoErrors();

    expect($user->refresh()->hasConfirmedTwoFactor())->toBeTrue()
        ->and($component->get('recoveryCodes'))->not->toBeEmpty();
});

it('rejects a bogus confirmation code', function () {
    $user = User::factory()->create(['password' => bcrypt('correct-password-123')]);
    $user->assignRole('owner');

    $component = Livewire::actingAs($user)
        ->test(TwoFactorSetup::class)
        ->set('password', 'correct-password-123')
        ->call('enable');

    $component->set('code', '000000')
        ->call('confirm')
        ->assertHasErrors();

    expect($user->refresh()->hasConfirmedTwoFactor())->toBeFalse();
});

it('denies the component to guests', function () {
    // The component is only routed behind auth middleware; the route itself
    // must redirect guests (covered in AdminAccessTest) — this asserts the
    // page route carries the middleware.
    $this->get(route('admin.security.two-factor'))->assertRedirect('/login');
});
