<script>
    // Remove only retired caching workers. The notification worker deliberately
    // performs no fetch interception, so it cannot cause stale pages/login loops.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            const registrations = await navigator.serviceWorker.getRegistrations();
            const cacheNames = 'caches' in window ? await caches.keys() : [];
            const staleCaches = cacheNames.filter((name) => name.startsWith('rahs-cache-'));

            await Promise.all(registrations
                .filter((registration) => !registration.active?.scriptURL.endsWith('/push-sw.js'))
                .map((registration) => registration.unregister()));
            await Promise.all(staleCaches.map((name) => caches.delete(name)));

            // Cleanup is intentionally silent. Reloading here used to combine
            // with auth redirects and made the sign-in page appear to loop.
        });
    }
</script>
