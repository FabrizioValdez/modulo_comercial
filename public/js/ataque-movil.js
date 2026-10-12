/*
 * App móvil del Plan de Ataque (PWA).
 * - Guarda proyectos, personas y noticias en el teléfono (localStorage + service worker) para usarlos sin conexión.
 * - Con conexión: actualiza datos, trae noticias de varias fuentes y abre LinkedIn / Google.
 * Todo el texto se pinta con textContent: nunca se inserta HTML que venga de datos.
 */
(function () {
    'use strict';

    var script = document.currentScript;
    var URLS = {
        datos: script.dataset.datos,
        noticias: script.dataset.noticias,
        noticiasPersona: script.dataset.noticiasPersona,
        ficha: script.dataset.ficha,
        sw: script.dataset.sw,
        scope: script.dataset.scope
    };
    var CLAVE_DATOS = 'atq-datos';
    var CLAVE_NOT = 'atq-not-';
    var UNA_HORA = 3600 * 1000;

    var app = document.getElementById('atq-app');
    var chips = document.getElementById('atq-chips');
    var titulo = document.getElementById('atq-titulo');
    var back = document.getElementById('atq-back');
    var searchWrap = document.getElementById('atq-search-wrap');
    var q = document.getElementById('atq-q');
    var estado = document.getElementById('atq-estado');
    var dot = document.getElementById('atq-dot');

    var datos = leer(CLAVE_DATOS);
    var filtro = 'todas';

    /* ---------- utilidades ---------- */
    function leer(k) { try { return JSON.parse(localStorage.getItem(k)); } catch (e) { return null; } }
    function guardar(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* almacenamiento lleno o bloqueado */ } }
    function online() { return navigator.onLine; }
    function seguro(u) { return typeof u === 'string' && /^https?:\/\//i.test(u) ? u : null; }
    function fecha(iso) {
        if (!iso) { return ''; }
        var d = new Date(iso);
        return isNaN(d) ? '' : d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit' }) + ' ' + d.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
    }
    function norm(s) { return (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }

    // h('div', {class: 'x'}, 'texto', otroNodo)
    function h(tag, attrs) {
        var el = document.createElement(tag);
        Object.keys(attrs || {}).forEach(function (k) {
            var v = attrs[k];
            if (v === null || v === undefined || v === false) { return; }
            if (k === 'onclick') { el.addEventListener('click', v); } else if (k === 'text') { el.textContent = v; } else { el.setAttribute(k, v === true ? '' : v); }
        });
        for (var i = 2; i < arguments.length; i++) {
            var c = arguments[i];
            if (c === null || c === undefined || c === false) { continue; }
            if (Array.isArray(c)) { c.forEach(function (n) { if (n) { el.appendChild(n); } }); } else { el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); }
        }
        return el;
    }

    function enlace(texto, url, clase) {
        var u = seguro(url);
        return h('a', { class: 'btn ' + (clase || ''), href: u || '#', target: '_blank', rel: 'noopener noreferrer', 'aria-disabled': (!u || !online()) ? 'true' : null }, texto);
    }

    function avatar(p, grande) {
        var cls = 'av' + (grande ? ' lg' : '');
        if (p && p.foto) {
            var img = h('img', { class: cls, src: p.foto, alt: 'Foto de ' + (p.nombre || ''), loading: 'lazy' });
            img.addEventListener('error', function () { img.replaceWith(h('span', { class: cls, 'aria-hidden': 'true' }, p.iniciales || '?')); });
            return img;
        }
        if (p && p.nombre) { return h('span', { class: cls, 'aria-hidden': 'true' }, p.iniciales || '?'); }
        return h('span', { class: cls + ' empty', 'aria-hidden': 'true' }, '?');
    }

    function pintarEstado(extra) {
        var on = online();
        dot.classList.toggle('off', !on);
        var t = on ? 'En línea' : 'Sin conexión';
        if (datos && datos.generado) { t += ' · datos del ' + fecha(datos.generado); }
        estado.textContent = extra || t;
    }

    /* ---------- datos ---------- */
    function actualizarDatos() {
        if (!online()) { pintarEstado(); return Promise.resolve(false); }
        pintarEstado('Actualizando…');
        return fetch(URLS.datos, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) {
                var tipo = r.headers.get('content-type') || '';
                if (!r.ok || tipo.indexOf('json') === -1) { throw new Error(r.status === 401 || r.redirected ? 'login' : 'error'); }
                return r.json();
            })
            .then(function (json) { datos = json; guardar(CLAVE_DATOS, json); pintarEstado(); return true; })
            .catch(function (e) {
                pintarEstado(e.message === 'login' ? 'Inicia sesión en el CRM para actualizar' : 'No se pudo actualizar; se muestran los datos guardados');
                return false;
            });
    }

    function noticiasGuardadas(clave) { return leer(CLAVE_NOT + clave); }

    function traerNoticias(url, clave) {
        return fetch(url, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) { if (!r.ok || (r.headers.get('content-type') || '').indexOf('json') === -1) { throw new Error(); } return r.json(); })
            .then(function (json) { json.guardado = new Date().toISOString(); guardar(CLAVE_NOT + clave, json); return json; });
    }

    /* ---------- vistas ---------- */
    function pintarChips() {
        chips.innerHTML = '';
        if (!datos) { return; }
        var conteo = { todas: datos.proyectos.length };
        datos.proyectos.forEach(function (p) { conteo[p.prioridad] = (conteo[p.prioridad] || 0) + 1; });
        var opciones = [['todas', 'Todas']].concat(Object.keys(datos.prioridades).filter(function (k) { return conteo[k]; }).map(function (k) { return [k, datos.prioridades[k]]; }));
        opciones.forEach(function (o) {
            chips.appendChild(h('button', {
                type: 'button', class: 'chip' + (filtro === o[0] ? ' on' : ''), 'aria-pressed': filtro === o[0] ? 'true' : 'false',
                onclick: function () { filtro = o[0]; pintarLista(); }
            }, o[1] + ' · ' + conteo[o[0]]));
        });
    }

    function pintarLista() {
        titulo.textContent = 'Plan de Ataque';
        back.hidden = true;
        searchWrap.hidden = false;
        chips.hidden = false;
        pintarChips();
        app.innerHTML = '';

        if (!datos) {
            app.appendChild(h('div', { class: 'empty-state' }, online() ? 'Cargando datos…' : 'Sin conexión y sin datos guardados. Abre la app una vez con conexión.'));
            return;
        }

        var texto = norm(q.value.trim());
        var lista = datos.proyectos.filter(function (p) {
            if (filtro !== 'todas' && p.prioridad !== filtro) { return false; }
            if (!texto) { return true; }
            var bolsa = norm([p.empresa, p.proyecto, p.rubro].concat(p.personas.map(function (x) { return (x.nombre || '') + ' ' + (x.cargo || ''); })).join(' '));
            return bolsa.indexOf(texto) !== -1;
        });

        app.appendChild(h('div', { class: 'card warm' },
            h('div', { class: 'small' }, 'Guarda proyectos, fotos y noticias en el teléfono para usarlos sin conexión.'),
            h('div', { class: 'btns' }, h('button', { type: 'button', class: 'btn pri', onclick: guardarTodo }, 'Guardar todo para usar sin conexión')),
            h('div', { class: 'progress', id: 'atq-prog', hidden: true }, h('div'))
        ));

        if (!lista.length) { app.appendChild(h('div', { class: 'empty-state' }, 'Ninguna cuenta coincide.')); return; }

        lista.forEach(function (p) {
            var fotos = p.personas.filter(function (x) { return x.foto; }).slice(0, 3);
            var stack = h('div', { class: 'stack' }, (fotos.length ? fotos : p.personas.slice(0, 1)).map(function (x) { return avatar(x); }));
            if (!p.personas.length) { stack = h('div', { class: 'stack' }, avatar(null)); }
            app.appendChild(h('a', { class: 'card item', href: '#p/' + p.id },
                stack,
                h('div', { class: 'item-body' },
                    h('div', { class: 'emp' }, p.empresa),
                    h('div', { class: 'proj' }, p.proyecto),
                    h('div', { class: 'meta' },
                        h('span', { class: 'tag ' + p.prioridad }, p.prioridad_label),
                        p.alerta ? h('span', { class: 'tag alerta' }, 'Alerta') : null,
                        h('span', { class: 'tag' }, p.personas.length + ' personas')
                    )
                )
            ));
        });
    }

    function pintarProyecto(id) {
        var p = datos && datos.proyectos.find(function (x) { return String(x.id) === String(id); });
        if (!p) { location.hash = ''; return; }
        titulo.textContent = p.empresa;
        back.hidden = false;
        searchWrap.hidden = true;
        chips.hidden = true;
        app.innerHTML = '';
        window.scrollTo(0, 0);

        app.appendChild(h('section', { class: 'card hero' },
            h('div', { class: 'meta' }, h('span', { class: 'tag ' + p.prioridad }, p.prioridad_label), p.viabilidad ? h('span', { class: 'tag ok' }, 'Viabilidad ' + p.viabilidad) : null),
            h('h2', { style: 'margin-top:10px' }, p.proyecto),
            h('div', { class: 'small' }, [p.rubro, p.inversion, p.ejecucion, p.contratista ? 'Contratista: ' + p.contratista : null].filter(Boolean).join(' · ')),
            h('div', { class: 'btns' },
                enlace('LinkedIn empresa', p.enlaces.linkedin, 'li'),
                enlace('Google Noticias', p.enlaces.noticias),
                enlace('Google', p.enlaces.google),
                h('a', { class: 'btn', href: URLS.ficha.replace('__ID__', p.id) }, 'Ficha completa')
            )
        ));

        if (p.alerta) { app.appendChild(h('div', { class: 'alerta', role: 'alert' }, p.alerta)); }

        if (p.resumen) {
            app.appendChild(h('section', { class: 'card warm' }, h('h3', {}, 'Lo actual'), h('div', {}, p.resumen)));
        }
        if (p.datos_empresa) {
            app.appendChild(h('section', { class: 'card' }, h('h3', {}, 'La empresa hoy'), h('div', {}, p.datos_empresa), p.datos_fuente ? h('div', { class: 'small', style: 'margin-top:6px' }, 'Fuente: ' + p.datos_fuente) : null));
        }

        // Personas
        var personas = h('section', { class: 'card' }, h('h3', {}, 'Personas (' + p.personas.length + ')'));
        p.personas.forEach(function (x) {
            var cajaNoticias = h('div', { class: 'small' });
            personas.appendChild(h('div', { class: 'persona' },
                avatar(x, true),
                h('div', { class: 'p-body' },
                    h('div', { class: 'p-name' }, x.nombre || 'Por identificar'),
                    h('div', { class: 'small' }, x.cargo || ''),
                    x.universidad ? h('div', { class: 'small muted' }, x.universidad) : null,
                    x.nota ? h('div', { class: 'small', style: 'color:#6B4A0C' }, x.nota) : null,
                    h('div', { class: 'meta' }, h('span', { class: 'tag' + (x.rol === 'decisor' ? ' inmediato' : '') }, x.rol_label), x.sugerido ? h('span', { class: 'tag warn' }, 'Sugerido por IA') : null),
                    x.nombre ? h('div', { class: 'btns' },
                        enlace(x.tiene_perfil ? 'Perfil LinkedIn' : 'Buscar en LinkedIn', x.linkedin, 'li'),
                        enlace('Google', x.google),
                        h('button', { type: 'button', class: 'btn', onclick: function () { cargarNoticiasPersona(x, cajaNoticias); } }, 'Noticias')
                    ) : null,
                    cajaNoticias
                )
            ));
        });
        app.appendChild(personas);

        if (p.materiales && p.materiales.length) {
            app.appendChild(h('section', { class: 'card' }, h('h3', {}, 'Qué venderle'), h('div', { class: 'mats' }, p.materiales.map(function (m) { return h('span', {}, m); }))));
        }
        if (p.proximo_paso) {
            app.appendChild(h('section', { class: 'card' }, h('h3', {}, 'Próximo paso'), h('div', {}, p.proximo_paso)));
        }

        // Noticias del proyecto
        var cajaNot = h('div', {});
        var info = h('div', { class: 'small muted' });
        app.appendChild(h('section', { class: 'card' },
            h('h3', {}, 'Noticias del proyecto'),
            info,
            cajaNot,
            h('div', { class: 'btns' }, h('button', { type: 'button', class: 'btn', onclick: function () { cargarNoticias(p, cajaNot, info, true); } }, 'Actualizar noticias'))
        ));
        cargarNoticias(p, cajaNot, info, false);

        if (p.actividades && p.actividades.length) {
            app.appendChild(h('section', { class: 'card' }, h('h3', {}, 'Actividad'), p.actividades.map(function (a) {
                return h('div', { class: 'act' }, h('div', { class: 'small muted' }, a.fecha || ''), h('div', {}, a.texto));
            })));
        }
    }

    function pintarNoticias(caja, json) {
        caja.innerHTML = '';
        if (!json || !json.items || !json.items.length) { caja.appendChild(h('div', { class: 'small muted' }, 'Sin noticias encontradas.')); return; }
        json.items.forEach(function (n) {
            var u = seguro(n.url);
            caja.appendChild(h('div', { class: 'news' },
                u ? h('a', { href: u, target: '_blank', rel: 'noopener noreferrer' }, n.titulo) : h('div', {}, n.titulo),
                h('div', { class: 'src' }, [n.medio, n.fecha, n.via && n.via !== n.medio ? 'vía ' + n.via : null].filter(Boolean).join(' · '))
            ));
        });
    }

    function cargarNoticias(p, caja, info, forzar) {
        var guardadas = noticiasGuardadas('p' + p.id);
        if (guardadas) { pintarNoticias(caja, guardadas); info.textContent = 'Guardadas el ' + fecha(guardadas.guardado); }
        var vieja = !guardadas || (Date.now() - new Date(guardadas.guardado).getTime() > UNA_HORA);
        if (!online()) { if (!guardadas) { info.textContent = 'Sin conexión: aún no hay noticias guardadas de este proyecto.'; } return; }
        if (!forzar && !vieja) { return; }
        info.textContent = 'Buscando noticias…';
        traerNoticias(URLS.noticias.replace('__ID__', p.id), 'p' + p.id)
            .then(function (json) { pintarNoticias(caja, json); info.textContent = 'Actualizadas el ' + fecha(json.guardado); })
            .catch(function () { info.textContent = guardadas ? 'No se pudo actualizar; se muestran las guardadas.' : 'No se pudieron cargar las noticias.'; });
    }

    function cargarNoticiasPersona(x, caja) {
        var clave = 'd' + x.id;
        var guardadas = noticiasGuardadas(clave);
        if (!online()) { if (guardadas) { pintarNoticias(caja, guardadas); } else { caja.textContent = 'Sin conexión.'; } return; }
        caja.textContent = 'Buscando…';
        traerNoticias(URLS.noticiasPersona.replace('__ID__', x.id), clave)
            .then(function (json) { pintarNoticias(caja, json); })
            .catch(function () { if (guardadas) { pintarNoticias(caja, guardadas); } else { caja.textContent = 'No se pudieron cargar.'; } });
    }

    /* Descarga datos, fotos y noticias de todos los proyectos para el modo sin conexión. */
    function guardarTodo() {
        if (!online()) { pintarEstado('Necesitas conexión para guardar'); return; }
        var barra = document.getElementById('atq-prog');
        actualizarDatos().then(function () {
            if (!datos) { return; }
            barra.hidden = false;
            var tareas = [];
            datos.proyectos.forEach(function (p) {
                tareas.push(function () { return traerNoticias(URLS.noticias.replace('__ID__', p.id), 'p' + p.id).catch(function () {}); });
                p.personas.forEach(function (x) { if (x.foto) { tareas.push(function () { return fetch(x.foto, { credentials: 'same-origin' }).catch(function () {}); }); } });
            });
            var hechas = 0;
            function siguiente() {
                if (!tareas.length) { barra.hidden = true; pintarEstado('Listo: todo guardado para usar sin conexión'); return; }
                return tareas.shift()().then(function () {
                    hechas++;
                    barra.firstChild.style.width = Math.round(hechas / (hechas + tareas.length) * 100) + '%';
                    return siguiente();
                });
            }
            // 3 descargas a la vez
            siguiente(); siguiente(); siguiente();
        });
    }

    /* ---------- navegación ---------- */
    function ruta() {
        var m = location.hash.match(/^#p\/(\d+)/);
        if (m && datos) { pintarProyecto(m[1]); } else { pintarLista(); }
    }

    back.addEventListener('click', function () { if (history.length > 1) { history.back(); } else { location.hash = ''; } });
    q.addEventListener('input', pintarLista);
    window.addEventListener('hashchange', ruta);
    window.addEventListener('online', function () { pintarEstado(); actualizarDatos().then(function (ok) { if (ok) { ruta(); } }); });
    window.addEventListener('offline', function () { pintarEstado(); });

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register(URLS.sw, { scope: URLS.scope }).catch(function () { /* sin modo offline */ });
    }

    pintarEstado();
    ruta();
    actualizarDatos().then(function (ok) { if (ok) { ruta(); } });
})();
