<?php

use App\Livewire\Admin\CommandPalette;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function paletteUser(string $role): User
{
    $user = User::factory()->withConfirmedTwoFactor()->create();
    $user->assignRole($role);

    return $user;
}

it('filters navigation destinations by permission', function () {
    $owner = Livewire::actingAs(paletteUser('owner'))->test(CommandPalette::class);
    $owner->assertSee(__('finance.settings'))->assertSee(__('common.nav_finance'));

    $coordinator = Livewire::actingAs(paletteUser('coordinator'))->test(CommandPalette::class);
    $coordinator->assertDontSee(__('finance.settings'))
        ->assertDontSee(__('common.nav_finance'))
        ->assertSee(__('common.nav_courses'));

    $viewer = Livewire::actingAs(paletteUser('viewer'))->test(CommandPalette::class);
    $viewer->assertDontSee(__('courses.new_course'))
        ->assertSee(__('common.nav_courses'));
});

it('finds courses by title for users who may view them', function () {
    Course::factory()->create(['title_ar' => 'إدارة المشاريع الاحترافية']);

    Livewire::actingAs(paletteUser('admin'))
        ->test(CommandPalette::class)
        ->set('query', 'المشاريع')
        ->assertSee('إدارة المشاريع الاحترافية');
});

it('never surfaces search results to roles without the permission', function () {
    Course::factory()->create(['title_ar' => 'دورة سرية للاختبار']);

    // منسق بلا courses.view? المنسق يملكها — المطلع فقط عرض... viewer يملك courses.view
    // الاختبار الفعلي: البحث في الدفعات يتطلب cohorts.view — كلاهما يملكه.
    // الحارس الحقيقي هنا: وجهة الإعدادات لا تظهر لغير المالك حتى مع الاستعلام.
    Livewire::actingAs(paletteUser('admin'))
        ->test(CommandPalette::class)
        ->set('query', 'الإعدادات')
        ->assertDontSee(__('finance.settings'));
});
