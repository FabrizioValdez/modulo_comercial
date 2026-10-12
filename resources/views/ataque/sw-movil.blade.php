/* Service worker de la app móvil del Plan de Ataque. Generado por Laravel. */
const VERSION = 'atq-movil-v{{ $version }}';
const BASE = @json(rtrim(parse_url(url(config('ataque.prefix')), PHP_URL_PATH), '/'));
const SHELL = @json([route('ataque.movil'), asset('js/ataque-movil.js'), asset('img/ataque-icon-192.png'), asset('img/ataque-icon-512.png')]);

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(VERSION)
            .then(function (c) { return Promise.all(SHELL.map(function (u) { return c.add(new Request(u, { credentials: 'same-origin' })).catch(function () {}); })); })
            .then(function () { return self.skipWaiting(); })
    );
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys()
            .then(function (keys) { return Promise.all(keys.filter(function (k) { return k.indexOf('atq-movil-') === 0 && k !== VERSION; }).map(function (k) { return caches.delete(k); })); })
            .then(function () { return self.clients.claim(); })
    );
});

// Primero la red; si no hay conexión, lo último guardado.
function redPrimero(req) {
    return fetch(req).then(function (res) {
        // Si la sesión venció, Laravel redirige al login: no lo guardamos.
        if (res.ok && !res.redirected) {
            var copia = res.clone();
            caches.open(VERSION).then(function (c) { c.put(req, copia); });
        }
        return res;
    }).catch(function () {
        return caches.match(req).then(function (r) { return r || caches.match(SHELL[0]); });
    });
}

// Primero lo guardado (fotos, script, fuentes); se actualiza en segundo plano.
function cachePrimero(req) {
    return caches.match(req).then(function (guardado) {
        var red = fetch(req).then(function (res) {
            if (res.ok || res.type === 'opaque') {
                var copia = res.clone();
                caches.open(VERSION).then(function (c) { c.put(req, copia); });
            }
            return res;
        }).catch(function () { return guardado; });
        return guardado || red;
    });
}

self.addEventListener('fetch', function (event) {
    var req = event.request;
    if (req.method !== 'GET') { return; }
    var url = new URL(req.url);

    if (url.origin === self.location.origin) {
        if (url.pathname.indexOf(BASE + '/movil') === 0) {
            event.respondWith(redPrimero(req));
        } else if (url.pathname.indexOf('/storage/') === 0 || url.pathname.indexOf('/js/ataque-movil') === 0 || url.pathname.indexOf('/img/ataque-icon') === 0) {
            event.respondWith(cachePrimero(req));
        }
    }
});
