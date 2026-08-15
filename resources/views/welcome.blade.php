<x-layouts.base title="نظام التصميم">
    {{-- ترويسة مؤقتة — صفحة استعراض مكونات المرحلة صفر، تُستبدل بصفحة الهبوط في المرحلة 3 --}}
    <header class="border-b border-line bg-cream">
        <div class="mx-auto max-w-5xl px-4 py-16 text-center sm:py-24">
            <svg class="mx-auto size-10 text-gold" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 0c.9 6.7 4.4 10.2 12 12-7.6 1.8-11.1 5.3-12 12-.9-6.7-4.4-10.2-12-12C7.6 10.2 11.1 6.7 12 0z" />
            </svg>

            <h1 class="mt-6 text-5xl text-navy sm:text-7xl">عمران</h1>

            <p class="mx-auto mt-4 max-w-xl text-lg text-muted">
                مركز عمران للتدريب والاستشارات — نظام التصميم الأساسي:
                الخطوط، الألوان، والمكونات القاعدية للمنصة.
            </p>

            <p class="mt-6 text-sm text-muted">
                عيّنة أرقام مالية:
                <span class="font-medium text-navy">640.500 {{ __('common.omr') }}</span>
                · عيّنة تاريخ:
                <span class="font-medium text-navy">15 أغسطس 2026</span>
            </p>
        </div>
    </header>

    <main class="mx-auto max-w-5xl space-y-16 px-4 py-12 pb-24" x-data>

        {{-- الأزرار --}}
        <section>
            <h2 class="text-2xl text-navy">الأزرار</h2>
            <div class="mt-1 h-0.5 w-12 bg-gold"></div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-button>{{ __('common.save') }}</x-button>
                <x-button variant="gold">{{ __('common.confirm') }}</x-button>
                <x-button variant="secondary">{{ __('common.cancel') }}</x-button>
                <x-button variant="ghost">{{ __('common.view') }}</x-button>
                <x-button variant="danger">{{ __('common.delete') }}</x-button>
                <x-button disabled>
                    <x-spinner class="size-4" />
                    {{ __('common.saving') }}
                </x-button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3">
                <x-button size="sm" variant="secondary">صغير</x-button>
                <x-button size="md" variant="secondary">متوسط</x-button>
                <x-button size="lg" variant="secondary">كبير</x-button>
            </div>
        </section>

        {{-- حقول الإدخال --}}
        <section>
            <h2 class="text-2xl text-navy">حقول الإدخال</h2>
            <div class="mt-1 h-0.5 w-12 bg-gold"></div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <x-input label="اسم الدورة" name="course_title" placeholder="مثال: مقدمة في الذكاء الاصطناعي" required />
                <x-input label="البريد الإلكتروني" name="email" type="email" placeholder="name@example.com" hint="يُستخدم لإرسال تأكيد التسجيل" />
                <x-input label="المبلغ" name="amount" type="text" inputmode="decimal" placeholder="0.000" hint="بالريال العماني — ثلاث منازل عشرية" />
                <x-input label="رقم الهاتف" name="phone" type="tel" error="رقم الهاتف غير صحيح" value="123" />
                <x-select label="التصنيف" name="category" placeholder="{{ __('common.choose') }}" required>
                    <option value="islamic">اسلامية</option>
                    <option value="ai">ذكاء اصطناعي</option>
                    <option value="tech">تقنية</option>
                    <option value="self">تطوير ذات</option>
                </x-select>
                <div class="sm:col-span-2">
                    <x-textarea label="وصف الدورة" name="description" rows="3" placeholder="اكتب وصفاً موجزاً…" hint="{{ __('common.optional') }}" />
                </div>
            </div>
        </section>

        {{-- الشارات --}}
        <section>
            <h2 class="text-2xl text-navy">الشارات</h2>
            <div class="mt-1 h-0.5 w-12 bg-gold"></div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-badge>مسودة</x-badge>
                <x-badge variant="gold">قريباً</x-badge>
                <x-badge variant="success">التسجيل مفتوح</x-badge>
                <x-badge variant="error">ملغاة</x-badge>
                <x-badge variant="warning">مقاعد محدودة</x-badge>
                <x-badge variant="info">عن بُعد</x-badge>
            </div>
        </section>

        {{-- البطاقات --}}
        <section>
            <h2 class="text-2xl text-navy">البطاقات</h2>
            <div class="mt-1 h-0.5 w-12 bg-gold"></div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <x-card>
                    <x-slot:header>
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="font-bold text-navy">دورة الذكاء الاصطناعي</h3>
                            <x-badge variant="success">التسجيل مفتوح</x-badge>
                        </div>
                    </x-slot:header>

                    <p class="text-sm text-muted">
                        مدخل عملي للذكاء الاصطناعي للعاملين في المجال الديني.
                        12 ساعة تدريبية على ثلاث جلسات.
                    </p>

                    <x-slot:footer>
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-navy">45.000 {{ __('common.omr') }}</span>
                            <x-button size="sm" variant="gold">{{ __('common.enrollment') }}</x-button>
                        </div>
                    </x-slot:footer>
                </x-card>

                <x-card>
                    <h3 class="font-bold text-navy">بطاقة بسيطة</h3>
                    <p class="mt-2 text-sm text-muted">بطاقة دون ترويسة أو تذييل — للمحتوى الحر.</p>
                </x-card>
            </div>
        </section>

        {{-- النوافذ والتنبيهات --}}
        <section>
            <h2 class="text-2xl text-navy">النوافذ والتنبيهات</h2>
            <div class="mt-1 h-0.5 w-12 bg-gold"></div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <x-button variant="secondary" x-on:click="$dispatch('open-modal', 'demo-modal')">فتح نافذة</x-button>
                <x-button variant="danger" x-on:click="$dispatch('open-modal', 'demo-confirm')">{{ __('common.delete') }}…</x-button>
                <x-button variant="secondary" x-on:click="window.toast('success', 'تم تسجيل مصروف بقيمة 42.500 ر.ع. لدورة الذكاء الاصطناعي')">تنبيه نجاح</x-button>
                <x-button variant="secondary" x-on:click="window.toast('error', 'تعذر الاتصال بالخادم — لم يُحفظ التغيير')">تنبيه خطأ</x-button>
                <x-button variant="secondary" x-on:click="window.toast('warning', 'المقاعد المتبقية: 3 فقط')">تنبيه تحذير</x-button>
                <x-button variant="secondary" x-on:click="window.toast('info', 'تم تحديث بيانات الدفعة')">تنبيه معلومة</x-button>
            </div>

            <x-modal name="demo-modal" title="نافذة تجريبية">
                <p class="text-sm text-muted">هذه نافذة منبثقة قاعدية — على الجوال تظهر من الأسفل، وعلى سطح المكتب في الوسط.</p>
                <x-slot:footer>
                    <x-button x-on:click="$dispatch('close-modal', 'demo-modal')">{{ __('common.confirm') }}</x-button>
                    <x-button variant="ghost" x-on:click="$dispatch('close-modal', 'demo-modal')">{{ __('common.cancel') }}</x-button>
                </x-slot:footer>
            </x-modal>

            <x-modal name="demo-confirm" title="حذف دورة «مقدمة في الفقه»">
                <p class="text-sm text-muted">{{ __('common.are_you_sure') }} {{ __('common.action_irreversible') }}.</p>
                <x-slot:footer>
                    <x-button variant="danger" x-on:click="$dispatch('close-modal', 'demo-confirm'); window.toast('success', 'تم حذف الدورة')">{{ __('common.delete') }}</x-button>
                    <x-button variant="ghost" x-on:click="$dispatch('close-modal', 'demo-confirm')">{{ __('common.cancel') }}</x-button>
                </x-slot:footer>
            </x-modal>
        </section>

        {{-- الحالات الفارغة والتحميل --}}
        <section>
            <h2 class="text-2xl text-navy">الحالات الفارغة والتحميل</h2>
            <div class="mt-1 h-0.5 w-12 bg-gold"></div>

            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <x-card :padding="false">
                    <x-empty-state
                        title="لا توجد دورات بعد"
                        description="أنشئ أول دورة لتظهر هنا مع تفاصيلها ومواعيد دفعاتها.">
                        <x-slot:action>
                            <x-button variant="gold">{{ __('common.add') }} {{ __('common.course') }}</x-button>
                        </x-slot:action>
                    </x-empty-state>
                </x-card>

                <x-card>
                    <div class="flex items-center gap-4">
                        <x-skeleton variant="circle" />
                        <div class="flex-1 space-y-2">
                            <x-skeleton class="w-3/5" />
                            <x-skeleton class="w-2/5" />
                        </div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <x-skeleton variant="block" />
                        <x-skeleton />
                        <x-skeleton class="w-4/5" />
                    </div>
                </x-card>
            </div>
        </section>

    </main>

    <footer class="border-t border-line py-8 text-center text-sm text-muted">
        {{ __('common.center_name') }} — المرحلة صفر: الأساس
    </footer>
</x-layouts.base>
