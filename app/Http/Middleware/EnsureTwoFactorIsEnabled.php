<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Spec §9.5: 2FA is mandatory for owner and admin. A user with one of those
 * roles and no confirmed 2FA is redirected to setup and can reach nothing else.
 */
class EnsureTwoFactorIsEnabled
{
    /**
     * Routes reachable while 2FA is not yet confirmed.
     *
     * @var array<int, string>
     */
    protected array $allowed = [
        'admin.security.two-factor',
        'logout',
        'password.confirm',
        'password.confirm.store',
        'two-factor.enable',
        'two-factor.confirm',
        'two-factor.disable',
        'two-factor.qr-code',
        'two-factor.secret-key',
        'two-factor.recovery-codes',
        'verification.notice',
        'verification.send',
        'verification.verify',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && $user->requiresTwoFactor()
            && ! $user->hasConfirmedTwoFactor()
            && ! $request->routeIs(...$this->allowed)) {
            return redirect()->route('admin.security.two-factor');
        }

        return $next($request);
    }
}
