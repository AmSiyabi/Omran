<?php

use App\Livewire\Admin\Catalog\CategoriesIndex;
use App\Livewire\Admin\Catalog\ClientsIndex;
use App\Livewire\Admin\Catalog\CohortForm;
use App\Livewire\Admin\Catalog\CohortShow;
use App\Livewire\Admin\Catalog\CohortsIndex;
use App\Livewire\Admin\Catalog\CourseForm;
use App\Livewire\Admin\Catalog\CoursesIndex;
use App\Livewire\Admin\Catalog\InstructorsIndex;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Security;
use App\Livewire\Admin\TwoFactorSetup;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

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

        // Placeholder until Phase 5 — permission-guarded so the role matrix
        // is enforceable (and testable) from day one.
        Route::view('/finance', 'admin.placeholders.finance')
            ->middleware('permission:finance.view')
            ->name('finance');
    });
