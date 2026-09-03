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
