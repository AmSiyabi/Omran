<x-layouts.public
    :title="__('public.contact_title')"
    :description="__('public.contact_lead')"
    :canonical="route('public.contact')"
>
    <section class="mx-auto max-w-2xl px-4 py-14 sm:px-6" aria-labelledby="contact-title">
        <h1 id="contact-title" class="text-4xl text-navy">{{ __('public.contact_title') }}</h1>
        <div class="mt-2 h-0.5 w-12 bg-gold" aria-hidden="true"></div>
        <p class="mt-4 text-muted">{{ __('public.contact_lead') }}</p>

        @if (session('contact_success'))
            <div class="mt-8 rounded-xl border border-success/30 bg-success-soft p-5" role="status">
                <p class="font-bold text-success">{{ __('public.contact_success') }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('public.contact.store') }}" class="mt-8 space-y-5">
            @csrf

            {{-- مصيدتا السخام: حقل مخفي + طابع زمني (لا كابتشا — spec Phase 3) --}}
            <div class="hidden" aria-hidden="true">
                <label>company website<input type="text" name="company_website" tabindex="-1" autocomplete="off"></label>
            </div>
            <input type="hidden" name="_started_at" value="{{ time() }}">

            <div class="grid gap-5 sm:grid-cols-2">
                <x-input :label="__('public.contact_name')" name="name" :value="old('name')" required autocomplete="name" />
                <x-input :label="__('public.contact_email')" name="email" type="email" :value="old('email')" required autocomplete="email" />
                <x-input :label="__('public.contact_phone')" name="phone" type="tel" :value="old('phone')" :hint="__('common.optional')" autocomplete="tel" />
                <x-input :label="__('public.contact_subject')" name="subject" :value="old('subject', request('subject'))" :hint="__('common.optional')" />
            </div>

            <x-textarea :label="__('public.contact_message')" name="message" rows="5" required>{{ old('message') }}</x-textarea>

            <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-lg bg-gold px-8 text-base font-bold text-navy-deep transition-colors hover:bg-gold-light sm:w-auto">
                {{ __('public.contact_send') }}
            </button>
        </form>
    </section>
</x-layouts.public>
