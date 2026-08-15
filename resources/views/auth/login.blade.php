<x-layouts.auth :title="__('auth.login_title')">
    @if (session('status'))
        <div class="mb-4 rounded-lg bg-success-soft p-3 text-sm text-success" role="status">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
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

        <x-input
            :label="__('auth.password_label')"
            name="password"
            type="password"
            required
            autocomplete="current-password"
        />

        <label class="flex min-h-11 items-center gap-2 text-sm text-navy">
            <input type="checkbox" name="remember" class="size-4 rounded border-line text-navy accent-[#16202f]">
            {{ __('auth.remember_me') }}
        </label>

        <x-button type="submit" class="w-full">{{ __('auth.login') }}</x-button>

        <p class="text-center">
            <a href="{{ route('password.request') }}" class="text-sm text-info underline-offset-4 hover:underline">
                {{ __('auth.forgot_password') }}
            </a>
        </p>
    </form>
</x-layouts.auth>
