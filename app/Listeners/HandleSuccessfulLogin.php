<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

/**
 * Spec §9.6: track last login, and email the user when a login arrives
 * from a device we have not seen before.
 */
class HandleSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $request = request();
        $ip = $request->ip();
        $userAgent = (string) $request->userAgent();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->saveQuietly();

        // Device identity = user agent only. Including the IP would page the
        // owners on every mobile-network address rotation.
        $deviceHash = hash('sha256', $userAgent);

        $known = $user->devices()->where('device_hash', $deviceHash)->first();

        if ($known !== null) {
            $known->forceFill([
                'ip_address' => $ip,
                'last_seen_at' => now(),
            ])->save();

            return;
        }

        $isFirstDevice = ! $user->devices()->exists();

        $user->devices()->create([
            'device_hash' => $deviceHash,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'last_seen_at' => now(),
        ]);

        // First login ever isn't an anomaly — no alert for the initial device.
        if ($isFirstDevice) {
            return;
        }

        try {
            $user->notify(new NewDeviceLogin($ip, now()));
        } catch (\Throwable $e) {
            // Mail failure must never block a login
            Log::warning('New-device notification failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }
}
