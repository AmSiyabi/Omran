<?php

namespace App\Livewire\Admin;

use App\Models\Cohort;
use App\Models\Course;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * لوحة الأوامر (Phase 7): Ctrl+K — تنقّل لأي شاشة، بحث في الدورات
 * والدفعات، إجراءات سريعة. كل وجهة مرشّحة بصلاحية صاحب الجلسة.
 */
class CommandPalette extends Component
{
    public string $query = '';

    /**
     * @return list<array{group: string, label: string, url: string, hint: ?string}>
     */
    #[Computed]
    public function results(): array
    {
        $user = auth()->user();
        $query = trim($this->query);

        $destinations = [
            ['can' => true, 'label' => __('common.nav_home'), 'route' => 'admin.dashboard'],
            ['can' => $user->can('courses.view'), 'label' => __('common.nav_courses'), 'route' => 'admin.courses'],
            ['can' => $user->can('courses.create'), 'label' => __('courses.new_course'), 'route' => 'admin.courses.create'],
            ['can' => $user->can('courses.view'), 'label' => __('courses.categories'), 'route' => 'admin.categories'],
            ['can' => $user->can('courses.view'), 'label' => __('courses.instructors'), 'route' => 'admin.instructors'],
            ['can' => $user->can('courses.view'), 'label' => __('courses.clients'), 'route' => 'admin.clients'],
            ['can' => $user->can('cohorts.view'), 'label' => __('courses.cohorts'), 'route' => 'admin.cohorts'],
            ['can' => $user->can('cohorts.create'), 'label' => __('courses.new_cohort'), 'route' => 'admin.cohorts.create'],
            ['can' => $user->can('finance.view'), 'label' => __('common.nav_finance'), 'route' => 'admin.finance'],
            ['can' => $user->can('finance.settle'), 'label' => __('finance.settlements'), 'route' => 'admin.finance.settlements'],
            ['can' => $user->can('reports.view'), 'label' => __('finance.reports'), 'route' => 'admin.reports'],
            ['can' => $user->can('reports.tax'), 'label' => __('finance.tax_estimate_title'), 'route' => 'admin.reports.tax'],
            ['can' => $user->can('settings.manage'), 'label' => __('finance.settings'), 'route' => 'admin.settings'],
            ['can' => true, 'label' => __('auth.security_title'), 'route' => 'admin.security'],
        ];

        $results = [];

        foreach ($destinations as $destination) {
            if (! $destination['can']) {
                continue;
            }

            if ($query !== '' && mb_stripos($destination['label'], $query) === false) {
                continue;
            }

            $results[] = [
                'group' => __('common.palette_nav'),
                'label' => $destination['label'],
                'url' => route($destination['route']),
                'hint' => null,
            ];
        }

        if (mb_strlen($query) >= 2) {
            if ($user->can('courses.view')) {
                foreach (Course::query()->where('title_ar', 'like', "%{$query}%")->limit(5)->get(['id', 'title_ar']) as $course) {
                    $results[] = [
                        'group' => __('common.palette_courses'),
                        'label' => $course->title_ar,
                        'url' => route('admin.courses.edit', $course),
                        'hint' => null,
                    ];
                }
            }

            if ($user->can('cohorts.view')) {
                $cohorts = Cohort::query()
                    ->with('course:id,title_ar')
                    ->where(fn ($builder) => $builder
                        ->where('code', 'like', "%{$query}%")
                        ->orWhere('title_override_ar', 'like', "%{$query}%")
                        ->orWhereHas('course', fn ($sub) => $sub->where('title_ar', 'like', "%{$query}%")))
                    ->latest('starts_at')
                    ->limit(5)
                    ->get();

                foreach ($cohorts as $cohort) {
                    $results[] = [
                        'group' => __('common.palette_cohorts'),
                        'label' => ($cohort->title_override_ar ?? $cohort->course->title_ar).' — '.$cohort->code,
                        'url' => route('admin.cohorts.show', $cohort),
                        'hint' => $cohort->status->label(),
                    ];
                }
            }
        }

        return $results;
    }

    public function render(): View
    {
        return view('livewire.admin.command-palette');
    }
}
