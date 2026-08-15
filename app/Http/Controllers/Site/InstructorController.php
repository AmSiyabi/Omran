<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Partner;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(): View
    {
        return view('site.instructors.index', [
            'partners' => Partner::query()
                ->where('is_active', true)
                ->where('public_profile_visible', true)
                ->get(['id', 'display_name_ar', 'bio_ar', 'photo_path']),
            'instructors' => Instructor::query()
                ->where('is_public', true)
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'specialization_ar', 'bio_ar', 'photo_path']),
        ]);
    }

    public function show(int $id): View
    {
        $instructor = Instructor::query()
            ->where('is_public', true)
            ->findOrFail($id);

        return view('site.instructors.show', [
            'instructor' => $instructor,
        ]);
    }
}
