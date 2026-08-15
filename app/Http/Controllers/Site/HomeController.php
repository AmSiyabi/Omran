<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'courses' => Course::query()
                ->where('is_published', true)
                ->with(['category:id,name_ar,accent_color,slug', 'media'])
                ->latest('published_at')
                ->take(6)
                ->get(),
            'categories' => Category::query()
                ->active()
                ->orderBy('sort_order')
                ->get(['id', 'slug', 'name_ar', 'accent_color']),
        ]);
    }
}
