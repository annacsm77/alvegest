const CACHE_NAME = 'apigest-v1.0.0';
const urlsToCache = [
  './', // La root (index.php)
  './mobile.php',
  './spostamento.php',
  './search_arnia.php',
  './includes/load_attivita.php', // Sebbene sia AJAX, mettiamo in cache il codice
  './css/styles.css',
  './manifest.json',
  // Aggiungi qui anche i link esterni critici se possibile (es. jQuery)
  'https://code.jquery.com/jquery-3.6.0.min.js' 
];

// 1. Installazione: Mette in cache i file necessari
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Opened cache');
        return cache.addAll(urlsToCache);
      })
  );
});

// 2. Fetch: Intercetta la richiesta di rete
self.addEventListener('fetch', event => {
  event.respondWith(
    // Prova a trovare la risorsa nella cache
    caches.match(event.request)
      .then(response => {
        // Se la risorsa è in cache, la restituisce
        if (response) {
          return response;
        }
        // Altrimenti, va in rete
        return fetch(event.request);
      })
  );
});

// 3. Attivazione: Pulisce le vecchie cache (opzionale ma consigliato)
self.addEventListener('activate', event => {
  const cacheWhitelist = [CACHE_NAME];
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheWhitelist.indexOf(cacheName) === -1) {
            console.log('Deleting old cache:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});
