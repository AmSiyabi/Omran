<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Self-service TOTP setup (spec §9.5). Mandatory for owner/admin — the 2fa
 * middleware routes them here until confirmed. Every action re-validates the
 * current password; recovery codes are shown exactly once.
 */
#[Layout('components.layouts.admin')]
class TwoFactorSetup extends Component
{
    public string $password = '';

    public string $code = '';

    /** @var array<int, string> */
    public array $recoveryCodes = [];

    public function enable(EnableTwoFactorAuthentication $enable): void
    {
        $user = $this->currentUser();

        $this->validatePassword($user);

        $enable($user);

        $this->reset('password', 'code');
    }

    public function confirm(ConfirmTwoFactorAuthentication $confirm): void
    {
        $user = $this->currentUser();

        $this->validate(['code' => ['required', 'string']]);

        $confirm($user, $this->code);

        // Shown once, immediately after confirmation (spec §9.5)
        $this->recoveryCodes = $user->recoveryCodes();
        $this->reset('code');

        $this->dispatch('toast', type: 'success', message: __('auth.two_factor_confirmed'));
    }

    public function regenerateRecoveryCodes(GenerateNewRecoveryCodes $generate): void
    {
        $user = $this->currentUser();

        $this->validatePassword($user);

        $generate($user);

        $this->recoveryCodes = $user->recoveryCodes();
        $this->reset('password');
    }

    public function disable(DisableTwoFactorAuthentication $disable): void
    {
        $user = $this->currentUser();

        $this->validatePassword($user);

        $disable($user);

        $this->reset('password', 'code');
        $this->recoveryCodes = [];
    }

    public function render(): View
    {
        $user = $this->currentUser();

        return view('livewire.admin.two-factor-setup', [
            'enabled' => $user->two_factor_secret !== null,
            'confirmed' => $user->hasConfirmedTwoFactor(),
            'required' => $user->requiresTwoFactor(),
            'qrCodeSvg' => $user->two_factor_secret !== null && ! $user->hasConfirmedTwoFactor()
                ? $user->twoFactorQrCodeSvg()
                : null,
            'secretKey' => $user->two_factor_secret !== null && ! $user->hasConfirmedTwoFactor()
                ? decrypt($user->two_factor_secret)
                : null,
        ])->title(__('auth.two_factor_title'));
    }

    protected function currentUser(): User
    {
        /** @var User */
        return auth()->user();
    }

    protected function validatePassword(User $user): void
    {
        $this->validate(['password' => ['required', 'string']]);

        if (! Hash::check($this->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }
    }
}
