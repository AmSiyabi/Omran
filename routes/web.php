<?php

use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Site\CohortController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\CourseController;
use App\Http\Controllers\Site\HomeController;
use App\Http\Controllers\Site\InstructorController;
use App\Http\Controllers\Site\JoinController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SitemapController;
use App\Livewire\Admin\Catalog\CategoriesIndex;
use App\Livewire\Admin\Catalog\ClientsIndex;
use App\Livewire\Admin\Catalog\CohortForm;
use App\Livewire\Admin\Catalog\CohortShow;
use App\Livewire\Admin\Catalog\CohortsIndex;
use App\Livewire\Admin\Catalog\CourseForm;
use App\Livewire\Admin\Catalog\CoursesIndex;
use App\Livewire\Admin\Catalog\InstructorsIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Enrollments\AttendanceSheet;
use App\Livewire\Admin\Enrollments\EnrollmentsIndex;
use App\Livewire\Admin\Finance\FinanceHub;
use App\Livewire\Admin\Finance\SettlementShow;
use App\Livewire\Admin\Finance\SettlementsIndex;
use App\Livewire\Admin\Reports\ReportsHub;
use App\Livewire\Admin\Reports\TaxScreen;
use App\Livewire\Admin\Security;
use App\Livewire\Admin\SettingsPage;
use App\Livewire\Admin\TwoFactorSetup;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| الموقع العام — Phase 3. Never shares middleware with admin (spec §9.3).
|--------------------------------------------------------------------------
*/
Route::name('public.')->group(function (): void {
    Route::get('/', HomeController::class)->name('home');

    Route::get('/courses', [CourseController::class, 'index'])->name('courses');
    Route::get('/courses/{slug}', [CourseController::class, 'show'])->name('courses.show');
    Route::get('/cohorts/{code}', CohortController::class)->name('cohorts.show');

    Route::get('/instructors', [InstructorController::class, 'index'])->name('instructors');
    Route::get('/instructors/{id}', [InstructorController::class, 'show'])
        ->whereNumber('id')
        ->name('instructors.show');

    Route::get('/about', [PageController::class, 'about'])->name('about');
    Route::get('/work', [PageController::class, 'work'])->name('work');

    Route::get('/contact', [ContactController::class, 'show'])->name('contact');
    Route::post('/contact', [ContactController::class, 'store'])
        ->middleware('throttle:contact')
        ->name('contact.store');

    // التسجيل عبر الروابط المولدة — Phase 4 (spec §7.5: ASCII path)
    Route::get('/join/{token}', [JoinController::class, 'show'])->name('join');
    Route::post('/join/{token}', [JoinController::class, 'store'])
        ->middleware('throttle:join')
        ->name('join.store');
});

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// ديناميكي حتى يكون رابط Sitemap مطلقاً كما يتطلب المعيار
Route::get('/robots.txt', function () {
    $lines = implode("\n", [
        'User-agent: *',
        'Allow: /',
        'Disallow: /admin',
        'Disallow: /login',
        '',
        'Sitemap: '.route('sitemap'),
    ]);

    return response($lines, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
})->name('robots');

/*
|--------------------------------------------------------------------------
| Admin — spec §9.3: auth + verified + role + 2fa on the whole group.
| Public routes never share a middleware group with admin routes.
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'role:owner|admin|coordinator|viewer', '2fa'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function (): void {
        Route::get('/', Dashboard::class)->name('dashboard');

        Route::get('/security', Security::class)->name('security');
        Route::get('/security/two-factor', TwoFactorSetup::class)->name('security.two-factor');

        Route::view('/more', 'admin.more')->name('more');

        // الكتالوج — Phase 2
        Route::middleware('permission:courses.view')->group(function (): void {
            Route::get('/courses', CoursesIndex::class)->name('courses');
            Route::get('/courses/create', CourseForm::class)->name('courses.create');
            Route::get('/courses/{course}/edit', CourseForm::class)->name('courses.edit');
            Route::get('/categories', CategoriesIndex::class)->name('categories');
            Route::get('/instructors', InstructorsIndex::class)->name('instructors');
            Route::get('/clients', ClientsIndex::class)->name('clients');
        });

        Route::middleware('permission:cohorts.view')->group(function (): void {
            Route::get('/cohorts', CohortsIndex::class)->name('cohorts');
            Route::get('/cohorts/create', CohortForm::class)->name('cohorts.create');
            Route::get('/cohorts/{cohort}', CohortShow::class)->whereNumber('cohort')->name('cohorts.show');
            Route::get('/cohorts/{cohort}/edit', CohortForm::class)->whereNumber('cohort')->name('cohorts.edit');
        });

        // التسجيلات والحضور — Phase 4
        Route::middleware('permission:enrollments.view')->group(function (): void {
            Route::get('/cohorts/{cohort}/enrollments', EnrollmentsIndex::class)
                ->whereNumber('cohort')
                ->name('cohorts.enrollments');
        });

        Route::middleware('permission:enrollments.manage')->group(function (): void {
            Route::get('/cohorts/{cohort}/attendance', AttendanceSheet::class)
                ->whereNumber('cohort')
                ->name('cohorts.attendance');
        });

        // المستندات — روابط موقعة مؤقتة فقط (spec §7.4)
        Route::get('/documents/{document}', [DocumentController::class, 'show'])
            ->whereNumber('document')
            ->middleware('signed')
            ->name('documents.show');

        // المالية — Phase 5
        Route::middleware('permission:finance.view')->group(function (): void {
            Route::get('/finance', FinanceHub::class)->name('finance');
        });

        Route::middleware('permission:finance.settle')->group(function (): void {
            Route::get('/finance/settlements', SettlementsIndex::class)->name('finance.settlements');
            Route::get('/finance/settlements/{settlement}', SettlementShow::class)
                ->whereNumber('settlement')
                ->name('finance.settlements.show');
        });

        // التقارير والضرائب — Phase 6
        Route::middleware('permission:reports.view')->group(function (): void {
            Route::get('/reports', ReportsHub::class)->name('reports');
        });

        Route::middleware('permission:reports.tax')->group(function (): void {
            Route::get('/reports/tax', TaxScreen::class)->name('reports.tax');
        });

        Route::middleware('permission:settings.manage')->group(function (): void {
            Route::get('/settings', SettingsPage::class)->name('settings');
        });
    });
