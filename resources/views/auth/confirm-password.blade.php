<x-layouts.auth :title="__('auth.confirm_password_title')">
    <p class="mb-4 text-sm text-muted">{{ __('auth.confirm_password_hint') }}</p>

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf

        <x-input
            :label="__('auth.password_label')"
            name="password"
            type="password"
            required
            autofocus
            autocomplete="current-password"
        />

        <x-button type="submit" class="w-full">{{ __('common.confirm') }}</x-button>
    </form>
</x-layouts.auth>
