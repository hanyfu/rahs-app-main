self.addEventListener('push', (event) => {
    let payload = { title: 'RAHS alert', message: 'A new operational alert requires your attention.', url: '/dashboard' };
    try { payload = { ...payload, ...event.data.json() }; } catch (_) {}
    event.waitUntil(self.registration.showNotification(payload.title, {
        body: payload.message,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: payload.url,
        renotify: true,
        data: { url: payload.url },
    }));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/dashboard', self.location.origin).href;
    event.waitUntil(clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
        const existing = windows.find((client) => client.url === target);
        return existing ? existing.focus() : clients.openWindow(target);
    }));
});
