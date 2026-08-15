<?php

use App\Enums\CohortStatus;
use App\Models\Category;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the landing page with the hero sequence and reveals', function () {
    Course::factory()->count(3)->published()->create();

    $this->get('/')
        ->assertOk()
        ->assertSee('lang="ar"', escape: false)
        ->assertSee('dir="rtl"', escape: false)
        ->assertSee('hero-seq-1', escape: false)
        ->assertSee('data-reveal', escape: false)
        ->assertSee(__('public.hero_thesis'))
        ->assertSee(__('public.admin_login'))
        ->assertSee('application/ld+json', escape: false)
        ->assertSee('"@context"', escape: false);
});

it('renders every static public page', function (string $route) {
    $this->get(route($route))->assertOk()->assertSee('dir="rtl"', escape: false);
})->with(['public.courses', 'public.about', 'public.work', 'public.instructors', 'public.contact']);

it('filters the catalog by category', function () {
    $islamic = Category::factory()->create(['slug' => 'islamic-t', 'name_ar' => 'اسلامية']);
    $tech = Category::factory()->create(['slug' => 'tech-t', 'name_ar' => 'تقنية']);

    Course::factory()->published()->create(['category_id' => $islamic->id, 'title_ar' => 'دورة الفقه المختبرية']);
    Course::factory()->published()->create(['category_id' => $tech->id, 'title_ar' => 'دورة البرمجة المختبرية']);

    $this->get(route('public.courses', ['category' => 'islamic-t']))
        ->assertOk()
        ->assertSee('دورة الفقه المختبرية')
        ->assertDontSee('دورة البرمجة المختبرية');
});

it('hides unpublished courses everywhere and 404s their detail pages', function () {
    $draft = Course::factory()->create(['is_published' => false, 'title_ar' => 'دورة غير منشورة']);

    $this->get('/')->assertDontSee('دورة غير منشورة');
    $this->get(route('public.courses'))->assertDontSee('دورة غير منشورة');
    $this->get(route('public.courses.show', $draft->slug))->assertNotFound();
});

it('shows a published course with outcomes and Course JSON-LD', function () {
    $course = Course::factory()->published()->create();

    $this->get(route('public.courses.show', $course->slug))
        ->assertOk()
        ->assertSee($course->title_ar)
        ->assertSee(__('public.course_outcomes'))
        ->assertSee('"@type": "Course"', escape: false);

    expect($course->refresh()->view_count)->toBe(1);
});

it('shows public cohorts and hides drafts and cancelled ones', function () {
    $course = Course::factory()->published()->create();

    $open = Cohort::factory()->create(['course_id' => $course->id]);
    $open->forceFill(['status' => CohortStatus::Open])->save();

    $draft = Cohort::factory()->create(['course_id' => $course->id]);

    $this->get(route('public.cohorts.show', $open->code))->assertOk()->assertSee($course->title_ar);
    $this->get(route('public.cohorts.show', $draft->code))->assertNotFound();
});

it('shows only public instructors and 404s hidden profiles', function () {
    $public = Instructor::factory()->create(['is_public' => true]);
    $hidden = Instructor::factory()->create(['is_public' => false, 'name_ar' => 'مدرب مخفي']);

    $this->get(route('public.instructors'))
        ->assertOk()
        ->assertSee($public->name_ar)
        ->assertDontSee('مدرب مخفي');

    $this->get(route('public.instructors.show', $hidden->id))->assertNotFound();
});

it('serves a valid sitemap listing published courses only', function () {
    $published = Course::factory()->published()->create();
    $draft = Course::factory()->create(['is_published' => false]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee($published->slug)
        ->assertDontSee($draft->slug);
});
