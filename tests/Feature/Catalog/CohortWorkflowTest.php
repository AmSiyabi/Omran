<?php

use App\Enums\CohortStatus;
use App\Exceptions\InvalidCohortTransition;
use App\Models\Cohort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('walks the happy path draft → announced → open → closed → delivered', function () {
    $cohort = Cohort::factory()->create();

    foreach ([CohortStatus::Announced, CohortStatus::Open, CohortStatus::Closed, CohortStatus::Delivered] as $next) {
        $cohort->transitionTo($next);
        expect($cohort->refresh()->status)->toBe($next);
    }
});

it('throws on invalid transitions and leaves status untouched', function (string $from, string $to) {
    $cohort = Cohort::factory()->create();
    $cohort->forceFill(['status' => $from])->save();

    expect(fn () => $cohort->transitionTo(CohortStatus::from($to)))
        ->toThrow(InvalidCohortTransition::class);

    expect($cohort->refresh()->status->value)->toBe($from);
})->with([
    'draft skips to open' => ['draft', 'open'],
    'draft skips to settled' => ['draft', 'settled'],
    'open goes backwards' => ['open', 'announced'],
    'settled is final' => ['settled', 'cancelled'],
    'cancelled is final' => ['cancelled', 'draft'],
    'delivered back to open' => ['delivered', 'open'],
]);

it('allows cancelling from any state before settled', function (string $from) {
    $cohort = Cohort::factory()->create();
    $cohort->forceFill(['status' => $from])->save();

    $cohort->transitionTo(CohortStatus::Cancelled);

    expect($cohort->refresh()->status)->toBe(CohortStatus::Cancelled);
})->with(['draft', 'announced', 'open', 'closed', 'delivered']);

it('logs status changes in the activity log', function () {
    $cohort = Cohort::factory()->create();

    $cohort->transitionTo(CohortStatus::Announced);

    $entry = Activity::query()
        ->where('log_name', 'cohorts')
        ->where('subject_id', $cohort->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->attribute_changes['attributes']['status'])->toBe('announced');
});
