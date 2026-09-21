// Minimální service worker – umožní instalaci aplikace na plochu (Android/Chrome).
// Neřeší offline režim (aplikace vyžaduje připojení k serveru), jen splňuje podmínky pro „Instalovat aplikaci".

const CACHE = 'konzolak-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))
        )).then(() => self.clients.claim())
    );
});

// Síť napřed; při výpadku zkusí cache (jen pro statiku, kterou si prohlížeč sám uložil).
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request).catch(() => caches.match(event.request))
    );
});

// ---- Push notifikace ----
self.addEventListener('push', (event) => {
    var data = {};
    try { data = event.data ? event.data.json() : {}; } catch (e) {}

    var title = data.title || 'Konzolák';
    var options = {
        body: data.body || '',
        icon: '/icons/icon-192.png',
        badge: '/icons/icon-192.png',
        data: { url: data.url || '/admin' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    var url = (event.notification.data && event.notification.data.url) || '/admin';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            for (var i = 0; i < clients.length; i++) {
                if (clients[i].url.indexOf(url) !== -1 && 'focus' in clients[i]) {
                    return clients[i].focus();
                }
            }
            if (self.clients.openWindow) {
                return self.clients.openWindow(url);
            }
        })
    );
});
