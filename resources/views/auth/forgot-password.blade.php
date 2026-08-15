<x-layouts.auth :title="__('auth.forgot_password_title')">
    <p class="mb-4 text-sm text-muted">{{ __('auth.forgot_password_hint') }}</p>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-soft p-3 text-sm text-success" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <x-input
            :label="__('auth.email')"
            name="email"
            type="email"
            :value="old('email')"
            required
            autofocus
            autocomplete="username"
        />

        <x-button type="submit" class="w-full">{{ __('auth.send_reset_link') }}</x-button>

        <p class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-info underline-offset-4 hover:underline">
                {{ __('auth.back_to_login') }}
            </a>
        </p>
    </form>
</x-layouts.auth>
