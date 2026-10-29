// Only fixed, public assets are cached. PHP, APIs, uploads and private data use network only.
const CACHE='healthconnect-static-v1';
const STATIC=['offline.html','assets/css/app.css','assets/css/style.css','assets/js/app.js','assets/vendor/bootstrap.min.css','assets/vendor/bootstrap.bundle.min.js','assets/icons/icon-192.png','assets/icons/icon-512.png'];
const URLS=STATIC.map(path=>new URL(path,self.registration.scope).href);
self.addEventListener('install',event=>{event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(URLS)).then(()=>self.skipWaiting()));});
self.addEventListener('activate',event=>{event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key.startsWith('healthconnect-static-')&&key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim()));});
self.addEventListener('fetch',event=>{
 if(event.request.method!=='GET')return;
 if(event.request.mode==='navigate'){
  event.respondWith(fetch(event.request).catch(()=>caches.match(new URL('offline.html',self.registration.scope).href)));return;
 }
 if(URLS.includes(event.request.url))event.respondWith(caches.match(event.request).then(cached=>cached||fetch(event.request)));
});
