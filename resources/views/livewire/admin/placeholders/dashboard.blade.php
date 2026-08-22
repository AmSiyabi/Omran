{{-- هيكل تحميل لوحة المؤشرات — لا مساحات فارغة أثناء الجلب (Phase 7) --}}
<div>
    <x-skeleton class="h-8 w-56" />
    <x-skeleton class="mt-2 h-4 w-32" />

    <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach (range(1, 4) as $i)
            <x-skeleton variant="block" class="h-28" />
        @endforeach
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <x-skeleton variant="block" class="h-40" />
        <x-skeleton variant="block" class="h-40" />
    </div>

    <x-skeleton variant="block" class="mt-6 h-48" />
</div>
