<?php

use Livewire\Livewire;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->beforeEach(function () {
        // #[Lazy] يعرض هيكلاً في أول طلب؛ الاختبارات تفحص المحتوى والتفويض
        // الفعليين — التحميل الكسول يُعطَّل هنا (الإنتاج يفوّض عند الترطيب)
        Livewire::withoutLazyLoading();
    })
    ->in('Feature', 'Unit');
