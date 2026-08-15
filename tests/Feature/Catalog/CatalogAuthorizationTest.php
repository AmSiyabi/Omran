<?php

use App\Livewire\Admin\Catalog\CategoriesIndex;
use App\Livewire\Admin\Catalog\CohortShow;
use App\Livewire\Admin\Catalog\CoursesIndex;
use App\Models\Category;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

it('lets all four roles reach the catalog pages', function (string $role) {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.courses'))
        ->assertOk();

    $this->actingAs(userWithRole($role))
        ->get(route('admin.cohorts'))
        ->assertOk();
})->with(['owner', 'admin', 'coordinator', 'viewer']);

it('blocks a viewer from creating a category — action method, not UI', function () {
    Livewire::actingAs(userWithRole('viewer'))
        ->test(CategoriesIndex::class)
        ->call('create')
        ->assertForbidden();
});

it('blocks a viewer from deleting a course via the action method', function () {
    $course = Course::factory()->create();

    Livewire::actingAs(userWithRole('viewer'))
        ->test(CoursesIndex::class)
        ->call('confirmDelete', $course->id)
        ->assertForbidden();
});

it('blocks a coordinator from deleting but not creating', function () {
    $coordinator = userWithRole('coordinator');
    $course = Course::factory()->create();

    // coordinator can open the create flow…
    Livewire::actingAs($coordinator)
        ->test(CategoriesIndex::class)
        ->call('create')
        ->assertHasNoErrors();

    // …but delete is owner/admin only (courses.delete)
    Livewire::actingAs($coordinator)
        ->test(CoursesIndex::class)
        ->call('confirmDelete', $course->id)
        ->assertForbidden();
});

it('blocks a viewer from publishing a course', function () {
    $course = Course::factory()->create(['is_published' => false]);

    Livewire::actingAs(userWithRole('viewer'))
        ->test(CoursesIndex::class)
        ->call('togglePublish', $course->id)
        ->assertForbidden();

    expect($course->refresh()->is_published)->toBeFalse();
});

it('blocks a viewer from transitioning a cohort status', function () {
    $cohort = Cohort::factory()->create();

    Livewire::actingAs(userWithRole('viewer'))
        ->test(CohortShow::class, ['cohort' => $cohort->id])
        ->call('confirmTransition', 'announced')
        ->assertForbidden();

    expect($cohort->refresh()->status->value)->toBe('draft');
});

it('rejects tampering with the locked cohortId property', function () {
    $cohort = Cohort::factory()->create();
    $other = Cohort::factory()->create();

    expect(fn () => Livewire::actingAs(userWithRole('owner'))
        ->test(CohortShow::class, ['cohort' => $cohort->id])
        ->set('cohortId', $other->id)
    )->toThrow(Exception::class);
});

it('rejects tampering with the locked editingId on categories', function () {
    Category::factory()->create();

    expect(fn () => Livewire::actingAs(userWithRole('owner'))
        ->test(CategoriesIndex::class)
        ->set('editingId', 999)
    )->toThrow(Exception::class);
});
