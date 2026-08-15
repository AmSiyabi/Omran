<?php

use App\Livewire\Admin\Catalog\CohortForm;
use App\Livewire\Admin\Catalog\CourseForm;
use App\Models\Category;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\InvoicingEntity;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->owner = User::factory()->withConfirmedTwoFactor()->create();
    $this->owner->assignRole('owner');
});

it('shows working empty states on every catalog list', function (string $route, string $emptyTitle) {
    $this->actingAs($this->owner)
        ->get(route($route))
        ->assertOk()
        ->assertSee($emptyTitle);
})->with([
    ['admin.courses', 'لا توجد دورات بعد'],
    ['admin.cohorts', 'لا توجد دفعات بعد'],
    ['admin.instructors', 'لا يوجد مدربون بعد'],
    ['admin.clients', 'لا يوجد عملاء بعد'],
]);

it('renders course and cohort lists and detail without lazy loading (N+1 guard)', function () {
    // preventLazyLoading is active outside production — any lazy load throws
    $category = Category::factory()->create();
    $courses = Course::factory()->count(5)->create(['category_id' => $category->id]);
    $cohort = Cohort::factory()->create(['course_id' => $courses->first()->id]);
    $cohort->sessions()->create([
        'session_number' => 1,
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeek()->addHours(3),
    ]);

    $this->actingAs($this->owner)->get(route('admin.courses'))->assertOk();
    $this->actingAs($this->owner)->get(route('admin.cohorts'))->assertOk();
    $this->actingAs($this->owner)->get(route('admin.cohorts.show', $cohort))->assertOk();
    $this->actingAs($this->owner)->get(route('admin.categories'))->assertOk();
});

it('loads the course list with 200 seeded courses in under 300ms', function () {
    $categories = Category::factory()->count(6)->create();
    Course::factory()->count(200)->create([
        'category_id' => fn () => $categories->random()->id,
    ]);

    // warm framework caches with one request first
    $this->actingAs($this->owner)->get(route('admin.courses'))->assertOk();

    $start = hrtime(true);
    $this->actingAs($this->owner)->get(route('admin.courses'))->assertOk();
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;

    expect($elapsedMs)->toBeLessThan(300.0, "Course list took {$elapsedMs}ms");
});

it('creates a course through the form component with a generated slug', function () {
    $category = Category::factory()->create();

    Livewire\Livewire::actingAs($this->owner)
        ->test(CourseForm::class)
        ->set('title_ar', 'دورة اختبارية في الفقه')
        ->set('summary_ar', 'ملخص')
        ->set('description_ar', 'وصف تفصيلي')
        ->set('outcomes_ar', ['مخرج أول'])
        ->set('duration_hours', '12')
        ->set('level', 'all')
        ->set('category_id', (string) $category->id)
        ->call('save')
        ->assertHasNoErrors();

    $course = Course::query()->where('title_ar', 'دورة اختبارية في الفقه')->first();

    expect($course)->not->toBeNull()
        ->and($course->slug)->toMatch('/^[a-z0-9\-]+$/');
});

it('creates a cohort with auto-generated code and baisa price', function () {
    $course = Course::factory()->create();
    InvoicingEntity::factory()->create(['is_default' => true]);

    Livewire\Livewire::actingAs($this->owner)
        ->test(CohortForm::class)
        ->set('course_id', (string) $course->id)
        ->set('starts_at', now()->addMonth()->format('Y-m-d\TH:i'))
        ->set('ends_at', now()->addMonth()->addDays(2)->format('Y-m-d\TH:i'))
        ->set('price', '45.500')
        ->call('save')
        ->assertHasNoErrors();

    $cohort = Cohort::query()->where('course_id', $course->id)->first();

    expect($cohort)->not->toBeNull()
        ->and($cohort->price_baisa)->toBe(45500)
        ->and($cohort->code)->toMatch('/^[A-Z0-9\-]+$/');
});
