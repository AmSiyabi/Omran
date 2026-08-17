<?php

use App\Enums\CohortStatus;
use App\Enums\EnrollmentStatus;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\RegistrationLink;
use App\Notifications\EnrollmentReceived;
use App\Registration\RegisterParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function openCohort(array $attributes = []): Cohort
{
    $cohort = Cohort::factory()->create($attributes);
    $cohort->forceFill(['status' => CohortStatus::Open])->save();

    return $cohort;
}

function linkFor(Cohort $cohort, array $attributes = []): RegistrationLink
{
    return RegistrationLink::factory()->create(['cohort_id' => $cohort->id, ...$attributes]);
}

function joinPayload(int $i = 1): array
{
    return [
        'full_name_ar' => "مشارك تجريبي {$i}",
        'email' => "participant{$i}@example.com",
        'phone' => '9123456'.$i,
    ];
}

it('shows the registration form for a usable link', function () {
    $link = linkFor(openCohort());

    $this->get(route('public.join', $link->token))
        ->assertOk()
        ->assertSee(__('courses.join_title'))
        ->assertSee(__('courses.join_submit'));
});

it('registers a participant as confirmed, takes a seat, and emails them', function () {
    Notification::fake();

    $cohort = openCohort(['capacity' => 10, 'price_baisa' => 45500, 'is_free' => false]);
    $link = linkFor($cohort);

    $this->post(route('public.join.store', $link->token), joinPayload())
        ->assertRedirect(route('public.join', $link->token))
        ->assertSessionHas('joined_status', 'confirmed');

    $enrollment = Enrollment::query()->first();

    expect($enrollment->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($enrollment->amount_due_baisa)->toBe(45500)
        ->and($cohort->refresh()->seats_taken)->toBe(1)
        ->and($link->refresh()->uses_count)->toBe(1);

    Notification::assertSentOnDemand(EnrollmentReceived::class);
});

it('uses the link price override over the cohort price', function () {
    $cohort = openCohort(['price_baisa' => 45500, 'is_free' => false]);
    $link = linkFor($cohort, ['price_override_baisa' => 30000]);

    $this->post(route('public.join.store', $link->token), joinPayload());

    expect(Enrollment::query()->first()->amount_due_baisa)->toBe(30000);
});

it('waitlists everyone beyond capacity — 50 registrations, 10 seats', function () {
    $cohort = openCohort(['capacity' => 10]);
    $link = linkFor($cohort);
    $service = app(RegisterParticipant::class);

    foreach (range(1, 50) as $i) {
        $service->handle($link, joinPayload($i));
    }

    expect(Enrollment::query()->where('status', EnrollmentStatus::Confirmed)->count())->toBe(10)
        ->and(Enrollment::query()->where('status', EnrollmentStatus::Waitlisted)->count())->toBe(40)
        ->and($cohort->refresh()->seats_taken)->toBe(10);
});

it('keeps approval-required registrations pending without holding a seat', function () {
    $cohort = openCohort(['capacity' => 10]);
    $link = linkFor($cohort, ['requires_approval' => true]);

    $this->post(route('public.join.store', $link->token), joinPayload())
        ->assertSessionHas('joined_status', 'pending');

    expect($cohort->refresh()->seats_taken)->toBe(0);
});

it('rejects a duplicate email for the same cohort with an Arabic message', function () {
    $link = linkFor(openCohort());

    $this->post(route('public.join.store', $link->token), joinPayload());

    $this->post(route('public.join.store', $link->token), joinPayload())
        ->assertSessionHasErrors('email');

    expect(Enrollment::query()->count())->toBe(1);
});

it('shows a friendly Arabic message for every unusable link state', function (array $linkAttributes, ?string $cohortStatus, string $message) {
    $cohort = openCohort();

    if ($cohortStatus !== null) {
        $cohort->forceFill(['status' => $cohortStatus])->save();
    }

    $link = linkFor($cohort, $linkAttributes);

    $this->get(route('public.join', $link->token))
        ->assertOk()
        ->assertSee($message)
        ->assertDontSee(__('courses.join_submit'));

    // POST also refuses to create anything
    $this->post(route('public.join.store', $link->token), joinPayload())
        ->assertRedirect(route('public.join', $link->token));

    expect(Enrollment::query()->count())->toBe(0);
})->with([
    'revoked' => [['is_active' => false], null, 'هذا الرابط لم يعد فعالاً.'],
    'expired' => [['expires_at' => now()->subDay()], null, 'انتهت صلاحية هذا الرابط.'],
    'exhausted' => [['max_uses' => 3, 'uses_count' => 3], null, 'اكتمل عدد التسجيلات عبر هذا الرابط.'],
    'cohort draft' => [[], 'draft', 'التسجيل غير متاح لهذه الدفعة حالياً.'],
    'cohort closed' => [[], 'closed', 'التسجيل غير متاح لهذه الدفعة حالياً.'],
]);

it('404s unknown tokens and sequential guesses', function () {
    $link = linkFor(openCohort());

    $this->get(route('public.join', 'definitely-not-a-token'))->assertNotFound();

    // spec: tokens are not enumerable — nearby mutations of a real token fail
    foreach (range(1, 10) as $i) {
        $guess = substr($link->token, 0, -1).chr(65 + $i);

        if ($guess === $link->token) {
            continue;
        }

        $this->get(route('public.join', $guess))->assertNotFound();
    }
});

it('rate limits the registration endpoint per IP', function () {
    $link = linkFor(openCohort());

    $status = null;

    foreach (range(1, 6) as $i) {
        $status = $this->post(route('public.join.store', $link->token), joinPayload($i))->getStatusCode();
    }

    expect($status)->toBe(429);
});

it('generates 32-character base62 tokens', function () {
    $token = RegistrationLink::generateToken();

    expect(strlen($token))->toBe(32)
        ->and($token)->toMatch('/^[0-9A-Za-z]+$/');
});
