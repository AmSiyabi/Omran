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
