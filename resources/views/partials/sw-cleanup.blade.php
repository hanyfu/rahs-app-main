<script>
    // The app previously shipped a PWA service worker. It has been retired, but
    // browsers keep the last registered worker controlling the origin until it
    // is unregistered here. Run on every layout (including the auth pages) so a
    // stale worker can never keep intercepting navigation to the login screen.
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            const registrations = await navigator.serviceWorker.getRegistrations();
            const cacheNames = 'caches' in window ? await caches.keys() : [];
            const staleCaches = cacheNames.filter((name) => name.startsWith('rahs-cache-'));

            await Promise.all(registrations.map((registration) => registration.unregister()));
            await Promise.all(staleCaches.map((name) => caches.delete(name)));

            // Cleanup is intentionally silent. Reloading here used to combine
            // with auth redirects and made the sign-in page appear to loop.
        });
    }
</script>
