// Service Worker برای PWA
const CACHE_NAME = 'exchange-rates-v2';
const urlsToCache = [
    '/',
    '/index.php',
    '/fonts/Vazir.ttf',
    '/manifest.json'
];

// نصب Service Worker
self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(function(cache) {
                console.log('Cache opened');
                return cache.addAll(urlsToCache);
            })
    );
});

// فعال‌سازی و حذف کش‌های قدیمی
self.addEventListener('activate', function(event) {
    const cacheWhitelist = [CACHE_NAME];
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(cacheName) {
                    if (cacheWhitelist.indexOf(cacheName) === -1) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// استراتژی: Cache First then Network
self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request)
            .then(function(response) {
                // اگر در کش موجود بود برگردان
                if (response) {
                    return response;
                }
                // در غیر این صورت از شبکه دریافت کن
                return fetch(event.request).then(function(networkResponse) {
                    // فقط پاسخ‌های موفق را کش کن
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME)
                            .then(function(cache) {
                                cache.put(event.request, responseToCache);
                            });
                    }
                    return networkResponse;
                });
            })
            .catch(function() {
                // در صورت عدم دسترسی به شبکه و کش
                return new Response('⚠️ خطا در اتصال به اینترنت', {
                    status: 503,
                    statusText: 'Service Unavailable'
                });
            })
    );
});

// پیام‌های ورودی
self.addEventListener('message', function(event) {
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});