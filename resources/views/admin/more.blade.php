<x-layouts.admin :title="__('common.nav_more')">
    <div class="mx-auto max-w-lg">
        <h1 class="text-2xl text-navy">{{ __('common.nav_more') }}</h1>

        <x-card class="mt-6" :padding="false">
            <ul class="divide-y divide-line">
                <li>
                    <a href="{{ route('admin.security') }}" wire:navigate class="flex min-h-14 items-center justify-between gap-3 px-5 text-navy transition-colors hover:bg-navy/5">
                        <span class="flex items-center gap-3">
                            <svg class="size-5 text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                            </svg>
                            {{ __('auth.security_title') }}
                        </span>
                        <svg class="size-4 rotate-180 text-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02z" clip-rule="evenodd" />
                        </svg>
                    </a>
                </li>
                <li class="px-5 py-4">
                    <p class="text-sm font-medium text-navy">{{ auth()->user()?->name_ar }}</p>
                    <p class="text-xs text-muted">{{ auth()->user()?->email }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm">{{ __('auth.logout') }}</x-button>
                    </form>
                </li>
            </ul>
        </x-card>
    </div>
</x-layouts.admin>
