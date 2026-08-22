/**
 * Toast stack — consumed by <x-toast/> (x-data="toastStack()").
 * Defined as a plain global so it exists before Livewire boots Alpine,
 * and kept out of inline <script> for CSP compatibility.
 *
 * Dispatch from anywhere:
 *   window.toast('success', 'تم الحفظ')
 * or from Livewire:
 *   $this->dispatch('toast', type: 'success', message: '...')
 */
window.toastStack = () => ({
    toasts: [],
    counter: 0,

    add(detail) {
        const type = ['success', 'error', 'warning', 'info'].includes(detail?.type)
            ? detail.type
            : 'info';
        const id = ++this.counter;

        this.toasts.push({ id, type, message: detail?.message ?? '', visible: true });

        // Errors persist until dismissed; everything else auto-dismisses (spec §12.2)
        if (type !== 'error') {
            setTimeout(() => this.dismiss(id), 4000);
        }
    },

    dismiss(id) {
        const toast = this.toasts.find((t) => t.id === id);
        if (toast) {
            toast.visible = false;
            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 200);
        }
    },
});

window.toast = (type, message) =>
    window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));

/**
 * حصر التركيز داخل النوافذ الحوارية (Phase 7). يُستدعى من keydown.tab
 * على حاوية النافذة: Tab يدور داخل عناصرها ولا يهرب خلف الطبقة المعتمة.
 */
window.trapTab = (container, event) => {
    const focusables = [
        ...container.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
        ),
    ].filter((el) => el.offsetParent !== null);

    if (focusables.length === 0) {
        event.preventDefault();

        return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    } else if (!container.contains(document.activeElement)) {
        event.preventDefault();
        first.focus();
    }
};

/**
 * تركيز أول عنصر قابل للتركيز داخل نافذة عند فتحها. يُعاد التأكيد بعد
 * استقرار الـmorph — سباق Livewire قد يعيد التركيز إلى body.
 */
window.focusFirstIn = (container) => {
    const attempt = () => {
        const panel = container.querySelector('[data-modal-panel]') ?? container;
        const target =
            panel.querySelector(
                'input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]):not([data-modal-close]), a[href]',
            ) ?? panel.querySelector('[data-modal-close]');

        target?.focus();
    };

    attempt();
    setTimeout(() => {
        if (container.isConnected && !container.contains(document.activeElement)) {
            attempt();
        }
    }, 300);
};

/**
 * لوحة الأوامر (Phase 7) — حالة Alpine: الفتح، تمييز النتيجة النشطة،
 * التنقل بالأسهم. النتائج نفسها يعيد Livewire تصييرها.
 */
window.commandPalette = ($wire) => ({
    open: false,
    active: 0,

    init() {
        this.$watch('open', (isOpen) => {
            if (!isOpen && $wire.query !== '') {
                $wire.$set('query', '');
            }
        });
    },

    items() {
        return [...this.$refs.list.querySelectorAll('[data-palette-item]')];
    },

    show() {
        this.open = true;
        this.$nextTick(() => {
            this.$refs.q.focus();
            this.highlight(0);
        });
    },

    hide() {
        this.open = false;
    },

    highlight(index) {
        const items = this.items();
        items.forEach((el, i) => el.classList.toggle('palette-active', i === index));
        this.active = index;
    },

    highlightElement(el) {
        this.highlight(this.items().indexOf(el));
    },

    move(delta) {
        const items = this.items();

        if (items.length === 0) {
            return;
        }

        const next = (this.active + delta + items.length) % items.length;
        this.highlight(next);
        items[next].scrollIntoView({ block: 'nearest' });
    },

    go() {
        this.items()[this.active]?.click();
    },
});

/**
 * حارس التغييرات غير المحفوظة (Phase 7): النماذج الكبيرة تعرض مؤشر
 * wire:dirty (data-dirty-marker). عند التنقل أو إغلاق الصفحة والمؤشر
 * ظاهر — نطلب تأكيداً.
 */
const visibleDirtyMarker = () => {
    const marker = document.querySelector('[data-dirty-marker]');

    return marker && getComputedStyle(marker).display !== 'none' ? marker : null;
};

document.addEventListener('livewire:navigate', (event) => {
    const marker = visibleDirtyMarker();

    if (marker && !window.confirm(marker.dataset.confirm)) {
        event.preventDefault();
    }
});

window.addEventListener('beforeunload', (event) => {
    if (visibleDirtyMarker()) {
        event.preventDefault();
    }
});

/**
 * PWA — static-asset service worker (admin + auth pages only; this bundle
 * is not loaded on the public site).
 */
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // التسجيل اختياري — فشله لا يعطل شيئاً
        });
    });
}
