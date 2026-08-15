<x-layouts.auth :title="__('auth.reset_password_title')">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-input
            :label="__('auth.email')"
            name="email"
            type="email"
            :value="old('email', $request->email)"
            required
            autocomplete="username"
        />

        <x-input
            :label="__('auth.new_password')"
            name="password"
            type="password"
            required
            autocomplete="new-password"
            :hint="__('auth.password_policy_hint')"
        />

        <x-input
            :label="__('auth.confirm_new_password')"
            name="password_confirmation"
            type="password"
            required
            autocomplete="new-password"
        />

        <x-button type="submit" class="w-full">{{ __('auth.reset_password') }}</x-button>
    </form>
</x-layouts.auth>
