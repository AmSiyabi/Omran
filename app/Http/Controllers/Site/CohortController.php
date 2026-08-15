<?php

namespace App\Http\Controllers\Site;

use App\Enums\CohortStatus;
use App\Http\Controllers\Controller;
use App\Models\Cohort;
use Illuminate\View\View;

class CohortController extends Controller
{
    /**
     * Publicly visible statuses only — drafts and cancelled cohorts 404.
     */
    public function __invoke(string $code): View
    {
        $cohort = Cohort::query()
            ->where('code', $code)
            ->whereIn('status', [
                CohortStatus::Announced,
                CohortStatus::Open,
                CohortStatus::Closed,
                CohortStatus::Delivered,
                CohortStatus::Settled,
            ])
            ->whereHas('course', fn ($query) => $query->where('is_published', true))
            ->with(['course.category:id,name_ar,accent_color,slug', 'course.media', 'sessions'])
            ->firstOrFail();

        return view('site.cohorts.show', [
            'cohort' => $cohort,
        ]);
    }
}
