// KazaERP service worker: cache aset statis berversi saja. JANGAN cache halaman sensitif/API.
const CACHE = 'kaza-v1';
const ASSETS = [];
self.addEventListener('install', (e) => { self.skipWaiting(); });
self.addEventListener('activate', (e) => { e.waitUntil(caches.keys().then((ks) => Promise.all(ks.filter((k) => k !== CACHE).map((k) => caches.delete(k))))); });
self.addEventListener('fetch', (e) => {
  const u = new URL(e.request.url);
  if (e.request.method !== 'GET') return;
  if (u.pathname.startsWith('/build/')) {
    e.respondWith(caches.open(CACHE).then((c) => c.match(e.request).then((m) => m || fetch(e.request).then((r) => { c.put(e.request, r.clone()); return r; }))));
  }
});
