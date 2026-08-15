<?php

use App\Livewire\Admin\Catalog\CohortShow;
use App\Models\Cohort;
use App\Models\CohortDeliverer;
use App\Models\Instructor;
use App\Models\Partner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->owner = User::factory()->withConfirmedTwoFactor()->create();
    $this->owner->assignRole('owner');
});

function makePartner(): Partner
{
    $user = User::factory()->create();

    return $user->partner()->create([
        'display_name_ar' => 'شريك '.$user->id,
        'bio_ar' => 'نبذة',
        'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
    ]);
}

it('rejects weights that do not sum to 100 with the Arabic message', function () {
    $cohort = Cohort::factory()->create();
    $partner = makePartner();
    $instructor = Instructor::factory()->create();

    Livewire::actingAs($this->owner)
        ->test(CohortShow::class, ['cohort' => $cohort->id])
        ->call('editDeliverers')
        ->set('delivererRows', [
            ['type' => 'partner', 'partner_id' => (string) $partner->id, 'instructor_id' => '', 'weight' => '60'],
            ['type' => 'external', 'partner_id' => '', 'instructor_id' => (string) $instructor->id, 'weight' => '30'],
        ])
        ->call('saveDeliverers')
        ->assertHasErrors(['delivererRows'])
        ->assertSee('مجموع نسب المنفذين يجب أن يساوي 100.00');

    expect($cohort->deliverers()->count())->toBe(0);
});

it('saves deliverers when weights sum to exactly 100', function () {
    $cohort = Cohort::factory()->create();
    $partner = makePartner();
    $instructor = Instructor::factory()->create();

    Livewire::actingAs($this->owner)
        ->test(CohortShow::class, ['cohort' => $cohort->id])
        ->call('editDeliverers')
        ->set('delivererRows', [
            ['type' => 'partner', 'partner_id' => (string) $partner->id, 'instructor_id' => '', 'weight' => '60'],
            ['type' => 'external', 'partner_id' => '', 'instructor_id' => (string) $instructor->id, 'weight' => '40'],
        ])
        ->call('saveDeliverers')
        ->assertHasNoErrors();

    expect($cohort->deliverers()->count())->toBe(2)
        ->and((float) $cohort->deliverers()->sum('share_weight'))->toBe(100.0);
});

it('accepts fractional weights that sum to 100 exactly', function () {
    $cohort = Cohort::factory()->create();
    $partnerOne = makePartner();
    $partnerTwo = makePartner();

    Livewire::actingAs($this->owner)
        ->test(CohortShow::class, ['cohort' => $cohort->id])
        ->call('editDeliverers')
        ->set('delivererRows', [
            ['type' => 'partner', 'partner_id' => (string) $partnerOne->id, 'instructor_id' => '', 'weight' => '33.33'],
            ['type' => 'partner', 'partner_id' => (string) $partnerTwo->id, 'instructor_id' => '', 'weight' => '66.67'],
        ])
        ->call('saveDeliverers')
        ->assertHasNoErrors();

    expect($cohort->deliverers()->count())->toBe(2);
});

it('rejects a row without an identity', function () {
    $cohort = Cohort::factory()->create();

    Livewire::actingAs($this->owner)
        ->test(CohortShow::class, ['cohort' => $cohort->id])
        ->call('editDeliverers')
        ->set('delivererRows', [
            ['type' => 'partner', 'partner_id' => '', 'instructor_id' => '', 'weight' => '100'],
        ])
        ->call('saveDeliverers')
        ->assertHasErrors(['delivererRows.0.type']);
});

it('enforces the identity invariant at the model layer too', function () {
    $cohort = Cohort::factory()->create();
    $partner = makePartner();
    $instructor = Instructor::factory()->create();

    // both identities set
    expect(fn () => CohortDeliverer::query()->create([
        'cohort_id' => $cohort->id,
        'deliverer_type' => 'partner',
        'partner_id' => $partner->id,
        'instructor_id' => $instructor->id,
        'share_weight' => 100,
    ]))->toThrow(DomainException::class);

    // type/identity mismatch
    expect(fn () => CohortDeliverer::query()->create([
        'cohort_id' => $cohort->id,
        'deliverer_type' => 'external',
        'partner_id' => $partner->id,
        'instructor_id' => null,
        'share_weight' => 100,
    ]))->toThrow(DomainException::class);
});
