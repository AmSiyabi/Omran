<?php

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

        // Placeholders until Phase 2 / Phase 5 — permission-guarded so the
        // role matrix is enforceable (and testable) from day one.
        Route::view('/courses', 'admin.placeholders.courses')
            ->middleware('permission:courses.view')
            ->name('courses');

        Route::view('/finance', 'admin.placeholders.finance')
            ->middleware('permission:finance.view')
            ->name('finance');
    });
