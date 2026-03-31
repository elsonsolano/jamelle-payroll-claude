const CACHE_NAME = 'payroll-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

// Handle incoming push notifications
self.addEventListener('push', (event) => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'Payroll System';
    const options = {
        body: data.body || '',
        icon: '/images/icons/icon-192.png',
        badge: '/images/icons/icon-192.png',
        data: { url: data.url || '/staff/dashboard' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Open the app when notification is tapped
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = event.notification.data?.url || '/staff/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
