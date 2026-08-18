<?php

namespace App\Livewire\Admin;

use App\Finance\DashboardMetrics;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        return view('livewire.admin.dashboard', [
            'user' => $user,
            // الأرقام المالية لمن يملك الاطلاع عليها فقط
            'metrics' => $user->can('finance.view') ? app(DashboardMetrics::class)->build() : null,
        ])->title(__('common.nav_home'));
    }
}
