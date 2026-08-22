{{-- هيكل تحميل المركز المالي --}}
<div class="mx-auto max-w-4xl">
    <x-skeleton class="h-8 w-44" />

    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach (range(1, 6) as $i)
            <x-skeleton variant="block" class="h-20" />
        @endforeach
    </div>

    <x-skeleton class="mt-8 h-6 w-32" />
    <div class="mt-3 space-y-px">
        @foreach (range(1, 6) as $i)
            <x-skeleton variant="block" class="h-16 rounded-none first:rounded-t-lg last:rounded-b-lg" />
        @endforeach
    </div>
</div>
