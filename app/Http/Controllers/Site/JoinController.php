<?php

namespace App\Http\Controllers\Site;

use App\Exceptions\LinkNotUsable;
use App\Http\Controllers\Controller;
use App\Models\RegistrationLink;
use App\Notifications\EnrollmentReceived;
use App\Registration\RegisterParticipant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class JoinController extends Controller
{
    public function show(string $token): View
    {
        $link = $this->findLink($token);

        $unusableReason = null;

        try {
            app(RegisterParticipant::class)->probe($link);
        } catch (LinkNotUsable $exception) {
            $unusableReason = $exception->reason;
        }

        return view('site.join', [
            'link' => $link,
            'cohort' => $link->cohort,
            'unusableReason' => $unusableReason,
            'joinedStatus' => session('joined_status'),
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $link = $this->findLink($token);

        $validated = $request->validate([
            'full_name_ar' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'organization_ar' => ['nullable', 'string', 'max:255'],
            'job_title_ar' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $enrollment = app(RegisterParticipant::class)->handle($link, $validated);
        } catch (LinkNotUsable) {
            // حالة الرابط تُعرض في صفحة GET — لا رسائل خطأ تقنية
            return redirect()->route('public.join', $token);
        }

        try {
            Notification::route('mail', $enrollment->email)
                ->notify(new EnrollmentReceived($enrollment));
        } catch (\Throwable $e) {
            Log::warning('Enrollment mail failed', ['enrollment_id' => $enrollment->id, 'error' => $e->getMessage()]);
        }

        return redirect()
            ->route('public.join', $token)
            ->with('joined_status', $enrollment->status->value);
    }

    protected function findLink(string $token): RegistrationLink
    {
        return RegistrationLink::query()
            ->where('token', $token)
            ->with(['cohort.course.category:id,name_ar,accent_color', 'cohort.course.media'])
            ->firstOrFail();
    }
}
