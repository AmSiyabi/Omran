<?php

use App\Enums\AttendanceStatus;
use App\Enums\CohortStatus;
use App\Enums\EnrollmentStatus;
use App\Livewire\Admin\Enrollments\AttendanceSheet;
use App\Livewire\Admin\Enrollments\EnrollmentsIndex;
use App\Livewire\Admin\Enrollments\RegistrationLinks;
use App\Models\AttendanceRecord;
use App\Models\Cohort;
use App\Models\Enrollment;
use App\Models\RegistrationLink;
use App\Models\User;
use App\Registration\RegisterParticipant;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function adminUser(string $role): User
{
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

it('approves a pending enrollment and grants a seat', function () {
    $cohort = Cohort::factory()->create(['capacity' => 10]);
    $enrollment = Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'status' => EnrollmentStatus::Pending,
    ]);

    Livewire::actingAs(adminUser('coordinator'))
        ->test(EnrollmentsIndex::class, ['cohort' => $cohort->id])
        ->call('approve', $enrollment->id);

    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Confirmed)
        ->and($cohort->refresh()->seats_taken)->toBe(1);
});

it('waitlists an approval when the cohort is full', function () {
    $cohort = Cohort::factory()->create(['capacity' => 1]);
    $cohort->forceFill(['seats_taken' => 1])->save();

    $enrollment = Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'status' => EnrollmentStatus::Pending,
    ]);

    app(RegisterParticipant::class)->approve($enrollment, adminUser('owner')->id);

    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Waitlisted)
        ->and($cohort->refresh()->seats_taken)->toBe(1);
});

it('cancelling a confirmed enrollment frees the seat', function () {
    $cohort = Cohort::factory()->create(['capacity' => 10]);
    $cohort->forceFill(['seats_taken' => 1])->save();

    $enrollment = Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'status' => EnrollmentStatus::Confirmed,
    ]);

    Livewire::actingAs(adminUser('coordinator'))
        ->test(EnrollmentsIndex::class, ['cohort' => $cohort->id])
        ->call('confirmCancel', $enrollment->id)
        ->call('cancelEnrollment');

    expect($enrollment->refresh()->status)->toBe(EnrollmentStatus::Cancelled)
        ->and($cohort->refresh()->seats_taken)->toBe(0);
});

it('bulk approves selected pending enrollments', function () {
    $cohort = Cohort::factory()->create(['capacity' => 10]);
    $enrollments = Enrollment::factory()->count(3)->create([
        'cohort_id' => $cohort->id,
        'status' => EnrollmentStatus::Pending,
    ]);

    Livewire::actingAs(adminUser('owner'))
        ->test(EnrollmentsIndex::class, ['cohort' => $cohort->id])
        ->set('selected', $enrollments->pluck('id')->all())
        ->call('bulkApprove');

    expect(Enrollment::query()->where('status', EnrollmentStatus::Confirmed)->count())->toBe(3)
        ->and($cohort->refresh()->seats_taken)->toBe(3);
});

it('blocks a viewer from managing enrollments', function () {
    $cohort = Cohort::factory()->create();
    $enrollment = Enrollment::factory()->create([
        'cohort_id' => $cohort->id,
        'status' => EnrollmentStatus::Pending,
    ]);

    // viewer has no enrollments.view → even the page mount is forbidden
    Livewire::actingAs(adminUser('viewer'))
        ->test(EnrollmentsIndex::class, ['cohort' => $cohort->id])
        ->assertForbidden();
});

it('only owner and admin can create registration links — coordinator cannot', function () {
    $cohort = Cohort::factory()->create();

    Livewire::actingAs(adminUser('coordinator'))
        ->test(RegistrationLinks::class, ['cohortId' => $cohort->id])
        ->call('create')
        ->assertForbidden();

    Livewire::actingAs(adminUser('admin'))
        ->test(RegistrationLinks::class, ['cohortId' => $cohort->id])
        ->call('create')
        ->assertHasNoErrors();
});

it('creates and revokes a link, and the revoked link stops registering', function () {
    $cohort = Cohort::factory()->create();
    $cohort->forceFill(['status' => CohortStatus::Open])->save();

    Livewire::actingAs(adminUser('owner'))
        ->test(RegistrationLinks::class, ['cohortId' => $cohort->id])
        ->call('create')
        ->set('label_ar', 'رابط وزارة الأوقاف')
        ->set('price_override', '30.000')
        ->call('save')
        ->assertHasNoErrors();

    $link = RegistrationLink::query()->first();

    expect($link->label_ar)->toBe('رابط وزارة الأوقاف')
        ->and($link->price_override_baisa)->toBe(30000);

    Livewire::actingAs(adminUser('owner'))
        ->test(RegistrationLinks::class, ['cohortId' => $cohort->id])
        ->call('confirmRevoke', $link->id)
        ->call('revoke');

    expect($link->refresh()->is_active)->toBeFalse();

    $this->post(route('public.join.store', $link->token), [
        'full_name_ar' => 'زائر',
        'email' => 'late@example.com',
        'phone' => '91234567',
    ])->assertRedirect(route('public.join', $link->token));

    expect(Enrollment::query()->count())->toBe(0);
});

it('cycles attendance status per tap and marks all present', function () {
    $cohort = Cohort::factory()->create();
    $session = $cohort->sessions()->create([
        'session_number' => 1,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(3),
    ]);

    $enrollments = Enrollment::factory()->count(3)->create([
        'cohort_id' => $cohort->id,
        'status' => EnrollmentStatus::Confirmed,
    ]);

    $component = Livewire::actingAs(adminUser('coordinator'))
        ->test(AttendanceSheet::class, ['cohort' => $cohort->id]);

    // نقرة أولى: حاضر — نقرة ثانية: غائب
    $component->call('toggle', $enrollments[0]->id);
    expect(AttendanceRecord::query()->where('enrollment_id', $enrollments[0]->id)->first()->status)
        ->toBe(AttendanceStatus::Present);

    $component->call('toggle', $enrollments[0]->id);
    expect(AttendanceRecord::query()->where('enrollment_id', $enrollments[0]->id)->first()->status)
        ->toBe(AttendanceStatus::Absent);

    $component->call('markAllPresent');

    expect(AttendanceRecord::query()->where('cohort_session_id', $session->id)->where('status', AttendanceStatus::Present)->count())
        ->toBe(3);
});

it('exports enrollments as XLSX for authorized roles only', function () {
    $cohort = Cohort::factory()->create();
    Enrollment::factory()->count(2)->create(['cohort_id' => $cohort->id]);

    Livewire::actingAs(adminUser('coordinator'))
        ->test(EnrollmentsIndex::class, ['cohort' => $cohort->id])
        ->call('export')
        ->assertFileDownloaded();
});
