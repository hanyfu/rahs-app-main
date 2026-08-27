// Retired service worker: remove all RAHS caches and unregister itself. The
// application requires a live backend and should not serve authenticated HTML
// from an offline cache.
self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(keys.filter((key) => key.startsWith('rahs-cache-')).map((key) => caches.delete(key)));
        await self.registration.unregister();
        await self.clients.claim();
    })());
});
