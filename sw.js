const CACHE_NAME = 'vision-hr-ess-v2';
const urlsToCache = [
    '/',
    '/ess-dashboard',
    '/ess-attendance',
    '/ess-leaves',
    '/ess-advances',
    '/ess-orders',
    '/ess-salary',
    '/ess-profile',
    // Critical CSS
    '/dist/css/brand.css',
    '/dist/css/ess-enhanced.css',
    '/dist/css/rtl-fixes.css',
    '/dist/css/tailwind-design-system.css',
    '/dist/css/responsive-global.css',
    '/dist/css/ui-fixes-v3.css',
    '/dist/css/responsive-fixes.css',
    // Core JS & Vendor CSS
    '/dist/js/app-ux.js',
    '/dist/img/brand/logo-icon.png',
    '/dist/img/brand/logo-secondary.png',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js'
];

self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
            .catch(function(err) {
                // Cache failed silently
            })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function(e) {
    e.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.filter(function(cacheName) {
                    return cacheName !== CACHE_NAME;
                }).map(function(cacheName) {
                    return caches.delete(cacheName);
                })
            );
        })
    );
    return self.clients.claim();
});

self.addEventListener('fetch', function(e) {
    // Skip non-HTTP(S) requests (chrome-extension:, data:, etc.)
    if (!e.request.url.startsWith('http')) {
        return;
    }
    
    // Skip if request method is not GET
    if (e.request.method !== 'GET') {
        return;
    }
    
    // For navigations (HTML), always go network-first so new PHP changes appear immediately
    if (e.request.mode === 'navigate') {
        e.respondWith(
            fetch(e.request)
                .then(function(response) {
                    // Optionally cache the latest HTML
                    const copy = response.clone();
                    caches.open(CACHE_NAME).then(cache => cache.put(e.request, copy));
                    return response;
                })
                .catch(function() {
                    return caches.match(e.request, { ignoreSearch: true });
                })
        );
        return;
    }

    // For assets (CSS/JS/images): cache-first with background refresh
    e.respondWith(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.match(e.request, { ignoreSearch: true }).then(function(cachedResponse) {
                return fetch(e.request)
                    .then(function(networkResponse) {
                        if (networkResponse && networkResponse.status === 200) {
                            cache.put(e.request, networkResponse.clone());
                        }
                        return networkResponse;
                    })
                    .catch(function() {
                        // Avoid throwing — serve cached version or a minimal fallback
                        return (
                            cachedResponse ||
                            caches.match(e.request, { ignoreSearch: true }) ||
                            new Response('', { status: 504, statusText: 'Offline' })
                        );
                    });
            });
        })
    );
});

// Handle push notifications
self.addEventListener('push', function(e) {
    const data = e.data ? e.data.json() : {};
    const title = data.title || 'Vision HR';
    const options = {
        body: data.body || 'لديك إشعار جديد',
        icon: '/dist/img/brand/logo-icon.png',
        badge: '/dist/img/brand/logo-icon.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.url || '/ess-dashboard'
        },
        dir: 'rtl',
        lang: 'ar'
    };
    e.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function(e) {
    e.notification.close();
    e.waitUntil(
        clients.openWindow(e.notification.data.url || '/ess-dashboard')
    );
});
