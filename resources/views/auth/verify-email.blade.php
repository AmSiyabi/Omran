<x-layouts.auth :title="__('auth.verify_email_title')">
    <p class="mb-4 text-sm text-muted">{{ __('auth.verify_email_hint') }}</p>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-success-soft p-3 text-sm text-success" role="status">
            {{ __('auth.verification_link_sent') }}
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
        @csrf
        <x-button type="submit" class="w-full">{{ __('auth.resend_verification') }}</x-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <x-button type="submit" variant="ghost" class="w-full">{{ __('auth.logout') }}</x-button>
    </form>
</x-layouts.auth>
