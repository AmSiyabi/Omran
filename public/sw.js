/**
 * مركز عمران — service worker (Phase 7).
 * Static assets only: fonts, brand images, Vite build output. Admin HTML,
 * Livewire endpoints, and anything dynamic are NEVER cached — financial
 * data must always come from the network.
 */
const CACHE = 'omran-static-v1';

const STATIC_PREFIXES = ['/build/', '/fonts/', '/images/'];
const STATIC_FILES = ['/favicon.ico', '/manifest.webmanifest'];

self.addEventListener('install', (event) => {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    const isStatic =
        STATIC_PREFIXES.some((prefix) => url.pathname.startsWith(prefix)) ||
        STATIC_FILES.includes(url.pathname);

    if (!isStatic) {
        return; // شبكة فقط — لا تخزين لأي صفحة أو بيانات
    }

    event.respondWith(
        caches.match(request).then(
            (cached) =>
                cached ??
                fetch(request).then((response) => {
                    if (response.ok) {
                        const copy = response.clone();
                        caches.open(CACHE).then((cache) => cache.put(request, copy));
                    }

                    return response;
                }),
        ),
    );
});
