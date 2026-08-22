<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Active-session list with revoke (spec §9.6). Requires the database
 * session driver — see docs/DECISIONS.md D-013.
 */
#[Layout('components.layouts.admin')]
class Security extends Component
{
    #[Locked]
    public ?string $revokingSessionId = null;

    /**
     * @return list<array{id: string, ip: ?string, browser: string, is_current: bool, last_active: Carbon}>
     */
    #[Computed]
    public function sessions(): array
    {
        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn (object $session): array => [
                'id' => (string) $session->id,
                'ip' => $session->ip_address === null ? null : (string) $session->ip_address,
                'browser' => $this->describeAgent($session->user_agent === null ? null : (string) $session->user_agent),
                'is_current' => (string) $session->id === session()->getId(),
                'last_active' => Carbon::createFromTimestamp((int) $session->last_activity, 'UTC')
                    ->timezone((string) config('app.display_timezone', 'Asia/Muscat')),
            ])
            ->values()
            ->all();
    }

    public function confirmRevoke(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            return;
        }

        $this->revokingSessionId = $sessionId;
    }

    public function cancelRevoke(): void
    {
        $this->revokingSessionId = null;
    }

    /**
     * @return array{id: string, ip: ?string, browser: string, is_current: bool, last_active: Carbon}|null
     */
    #[Computed]
    public function revokingSession(): ?array
    {
        foreach ($this->sessions() as $session) {
            if ($session['id'] === $this->revokingSessionId) {
                return $session;
            }
        }

        return null;
    }

    public function revoke(): void
    {
        // Scope to the authenticated user — a foreign session id is a no-op.
        // The current session cannot be revoked from here (use logout).
        if ($this->revokingSessionId === null || $this->revokingSessionId === session()->getId()) {
            return;
        }

        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', $this->revokingSessionId)
            ->delete();

        $this->revokingSessionId = null;
        unset($this->sessions);

        $this->dispatch('toast', type: 'success', message: __('auth.session_revoked'));
    }

    public function render(): View
    {
        return view('livewire.admin.security', [
            'user' => auth()->user(),
        ])->title(__('auth.security_title'));
    }

    protected function describeAgent(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return __('auth.unknown_device');
        }

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') && str_contains($userAgent, 'Version/') => 'Safari',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            default => __('auth.unknown_device'),
        };

        $platform = match (true) {
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Mac OS') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => '',
        };

        return trim($browser.($platform !== '' ? ' — '.$platform : ''));
    }
}
