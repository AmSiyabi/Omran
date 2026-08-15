<?php

namespace App\Http\Controllers\Site;

use App\Enums\CohortStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Partner;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return view('site.about', [
            'partners' => Partner::query()
                ->where('is_active', true)
                ->where('public_profile_visible', true)
                ->get(['id', 'display_name_ar', 'bio_ar', 'photo_path']),
        ]);
    }

    public function work(): View
    {
        $deliveredCohorts = Cohort::query()
            ->whereIn('status', [CohortStatus::Delivered, CohortStatus::Settled])
            ->whereHas('course', fn ($query) => $query->where('is_published', true))
            ->with(['course.category:id,name_ar,accent_color', 'course:id,title_ar,slug,category_id,duration_hours', 'client:id,name_ar'])
            ->orderByDesc('ends_at')
            ->take(12)
            ->get();

        return view('site.work', [
            'deliveredCohorts' => $deliveredCohorts,
            'clients' => Client::query()->orderBy('name_ar')->get(['id', 'name_ar', 'type']),
            'stats' => [
                'courses' => Course::query()->where('is_published', true)->count(),
                'cohorts' => Cohort::query()->whereIn('status', [CohortStatus::Delivered, CohortStatus::Settled])->count(),
                'hours' => (int) Course::query()->where('is_published', true)->sum('duration_hours'),
                'clients' => Client::query()->count(),
            ],
        ]);
    }
}
