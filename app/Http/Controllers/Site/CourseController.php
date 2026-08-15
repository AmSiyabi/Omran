<?php

namespace App\Http\Controllers\Site;

use App\Enums\CohortStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'name_ar', 'accent_color']);

        $currentCategory = null;
        $categorySlug = $request->string('category')->value();

        if ($categorySlug !== '') {
            $currentCategory = $categories->firstWhere('slug', $categorySlug);
        }

        $courses = Course::query()
            ->where('is_published', true)
            ->with(['category:id,name_ar,accent_color,slug', 'media'])
            ->when($currentCategory !== null, fn ($query) => $query->where('category_id', $currentCategory->id))
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('site.courses.index', [
            'courses' => $courses,
            'categories' => $categories,
            'currentCategory' => $currentCategory,
        ]);
    }

    public function show(string $slug): View
    {
        $course = Course::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->with(['category:id,name_ar,accent_color,slug', 'media'])
            ->firstOrFail();

        Course::query()->whereKey($course->id)->increment('view_count');

        $upcomingCohorts = $course->cohorts()
            ->whereIn('status', [CohortStatus::Announced, CohortStatus::Open])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->get();

        return view('site.courses.show', [
            'course' => $course,
            'upcomingCohorts' => $upcomingCohorts,
        ]);
    }
}
