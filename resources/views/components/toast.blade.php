{{--
    حاوية التنبيهات العامة — تُدرج مرة واحدة في التخطيط الأساسي.
    الإطلاق: window.toast('success', 'الرسالة')
    أو من Livewire: $this->dispatch('toast', type: 'success', message: '...')
    أسفل الوسط على الجوال، أعلى البداية على سطح المكتب. الأخطاء تبقى حتى تُغلق.
--}}
<div
    x-data="toastStack()"
    x-on:toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-0 bottom-4 z-[70] flex flex-col items-center gap-2 px-4 sm:inset-x-auto sm:bottom-auto sm:top-4 sm:start-4 sm:items-start sm:px-0"
    role="region"
    aria-label="{{ __('common.notifications') }}"
>
    <template x-for="t in toasts" :key="t.id">
        <div
            x-show="t.visible"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="translate-y-2 opacity-0 sm:translate-y-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-lg border bg-white p-4 shadow-lg"
            :class="{
                success: 'border-success/30',
                error: 'border-error/40',
                warning: 'border-warning/30',
                info: 'border-info/30',
            }[t.type]"
            role="alert"
        >
            <span
                class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full text-white"
                :class="{
                    success: 'bg-success',
                    error: 'bg-error',
                    warning: 'bg-warning',
                    info: 'bg-info',
                }[t.type]"
                aria-hidden="true"
            >
                <svg x-show="t.type === 'success'" class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143z" clip-rule="evenodd" /></svg>
                <svg x-show="t.type === 'error'" class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                <svg x-show="t.type === 'warning'" class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 6zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2z" clip-rule="evenodd" /></svg>
                <svg x-show="t.type === 'info'" class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0zM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9z" clip-rule="evenodd" /></svg>
            </span>

            <p class="flex-1 pt-0.5 text-sm text-navy" x-text="t.message"></p>

            <button
                type="button"
                class="-m-1 flex size-8 shrink-0 items-center justify-center rounded-full text-muted transition hover:bg-navy/5 hover:text-navy"
                x-on:click="dismiss(t.id)"
                aria-label="{{ __('common.close') }}"
            >
                <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
            </button>
        </div>
    </template>
</div>
