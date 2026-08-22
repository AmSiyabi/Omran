{{-- هيكل تحميل التقارير --}}
<div class="mx-auto max-w-5xl">
    <x-skeleton class="h-8 w-40" />

    <div class="mt-4 flex gap-2">
        @foreach (range(1, 5) as $i)
            <x-skeleton class="h-10 w-24 rounded-full" />
        @endforeach
    </div>

    <x-skeleton class="mt-4 h-11 w-64" />
    <x-skeleton variant="block" class="mt-4 h-96" />
</div>
