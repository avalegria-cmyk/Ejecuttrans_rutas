// La información autenticada y los registros siempre requieren red.
self.addEventListener('install',()=>self.skipWaiting());
self.addEventListener('activate',event=>event.waitUntil((async()=>{
    for(const key of await caches.keys()) await caches.delete(key);
    await self.clients.claim();
})()));
self.addEventListener('fetch',()=>{});
