const SHELL_CACHE = 'rpcom-pwa-shell-v1';
const ASSET_CACHE = 'rpcom-pwa-assets-v1';
const SHELL_URLS = ['/offline.html', '/icons/icon-192.png', '/icons/icon-512.png', '/icons/apple-touch-icon.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then((cache) => cache.addAll(SHELL_URLS))
            .then(() => self.skipWaiting()),
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => key.startsWith('rpcom-pwa-') && ![SHELL_CACHE, ASSET_CACHE].includes(key))
                .map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin || url.pathname === '/manifest.webmanifest') return;

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => (await caches.match('/offline.html')) || Response.error()),
        );
        return;
    }

    const isStaticAsset = url.pathname.startsWith('/build/assets/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/offline.html';

    if (!isStaticAsset) return;

    event.respondWith((async () => {
        const cached = await caches.match(request);
        if (cached) return cached;

        const response = await fetch(request);
        if (response.ok && response.type === 'basic') {
            const cache = await caches.open(ASSET_CACHE);
            cache.put(request, response.clone());
        }
        return response;
    })());
});
