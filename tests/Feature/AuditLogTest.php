<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('logs every role assignment in the activity log', function () {
    $user = User::factory()->create();

    $user->assignRole('coordinator');

    $entry = Activity::query()
        ->where('log_name', 'roles')
        ->where('event', 'role_attached')
        ->where('subject_type', User::class)
        ->where('subject_id', $user->id)
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->properties['roles'])->toContain('coordinator');
});

it('logs role removal in the activity log', function () {
    $user = User::factory()->create();
    $user->assignRole('coordinator');

    $user->removeRole('coordinator');

    $entry = Activity::query()
        ->where('log_name', 'roles')
        ->where('event', 'role_detached')
        ->where('subject_id', $user->id)
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->properties['roles'])->toContain('coordinator');
});

it('logs user attribute changes with old and new values', function () {
    $user = User::factory()->create(['name_ar' => 'قبل التعديل']);

    $user->update(['name_ar' => 'بعد التعديل']);

    $entry = Activity::query()
        ->where('log_name', 'users')
        ->where('subject_id', $user->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    // activitylog v5 stores diffs in the dedicated attribute_changes column
    expect($entry)->not->toBeNull()
        ->and($entry->attribute_changes['attributes']['name_ar'])->toBe('بعد التعديل')
        ->and($entry->attribute_changes['old']['name_ar'])->toBe('قبل التعديل');
});

it('never logs encrypted partner PII values', function () {
    $owner = User::factory()->create();

    $partner = $owner->partner()->create([
        'display_name_ar' => 'شريك',
        'bio_ar' => 'نبذة',
        'ownership_percent' => 50,
        'effective_from' => now()->toDateString(),
        'bank_account' => '1234567890',
        'civil_number' => '98765432',
    ]);

    $activities = Activity::query()
        ->where('log_name', 'partners')
        ->where('subject_id', $partner->id)
        ->get();

    expect($activities)->not->toBeEmpty();

    foreach ($activities as $activity) {
        $json = json_encode($activity->properties).json_encode($activity->attribute_changes);
        expect($json)->not->toContain('1234567890')
            ->and($json)->not->toContain('98765432');
    }
});
