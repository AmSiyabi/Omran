<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Models\User;
use App\Notifications\ContactMessageReceived;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('site.contact');
    }

    public function store(Request $request): RedirectResponse
    {
        // مصيدتا السخام (spec Phase 3: honeypot، لا كابتشا خارجية):
        // حقل مخفي يجب أن يبقى فارغاً + زمن أدنى لتعبئة النموذج
        $formAge = time() - (int) $request->input('_started_at', 0);

        if ($request->filled('company_website') || $formAge < 3) {
            // نتظاهر بالنجاح — لا نعطي الروبوت أي إشارة
            return redirect()
                ->route('public.contact')
                ->with('contact_success', true);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $message = ContactMessage::query()->create([
            ...$validated,
            'ip_address' => $request->ip(),
        ]);

        try {
            $owners = User::query()->role('owner')->where('is_active', true)->get();
            Notification::send($owners, new ContactMessageReceived($message));
        } catch (\Throwable $e) {
            Log::warning('Contact notification failed', ['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('public.contact')
            ->with('contact_success', true);
    }
}
