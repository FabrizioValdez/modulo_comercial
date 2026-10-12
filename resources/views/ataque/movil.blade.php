<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#17191C">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Plan de Ataque">
<title>Plan de Ataque</title>
<link rel="manifest" href="{{ route('ataque.movil.manifest') }}" crossorigin="use-credentials">
<link rel="apple-touch-icon" href="{{ asset('img/ataque-icon-192.png') }}">
<link rel="icon" href="{{ asset('img/ataque-icon-192.png') }}">
<style>
:root{--g:#F2F2EF;--s:#fff;--i:#17191C;--i2:#2E3237;--m:#5B6168;--l:#DADBD6;--l2:#ECECE8;--a:#C2410C;--ai:#9A3412;--w:#FFF7F2;--wl:#F5C9AE;--st:#2B4C66;--ok:#1E5140;--okb:#E1EEE7;--bl:#1F3A50;--blb:#DCE6EE;--am:#6B4A0C;--amb:#F6E7C8}
*{box-sizing:border-box}
html,body{margin:0;background:var(--g);color:var(--i);font:15px/1.45 system-ui,-apple-system,"Segoe UI",sans-serif;-webkit-text-size-adjust:100%}
a{color:var(--ai)}
button{font:inherit}
.top{position:sticky;top:0;z-index:5;background:var(--i);color:#F2F2EF;padding:calc(10px + env(safe-area-inset-top)) 16px 12px}
.top-row{display:flex;align-items:center;gap:10px;min-height:44px}
.logo{width:18px;height:20px;background:#EA580C;flex:none;clip-path:polygon(0 0,100% 0,100% 22%,62% 22%,62% 78%,100% 78%,100% 100%,0 100%,0 78%,38% 78%,38% 22%,0 22%)}
.top h1{font-size:17px;font-weight:800;letter-spacing:.03em;margin:0;flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.back{background:none;border:0;color:#F2F2EF;min-width:44px;min-height:44px;display:inline-flex;align-items:center;justify-content:center;margin-left:-10px;cursor:pointer}
.estado{font-size:12px;color:#C9CBC5;display:flex;align-items:center;gap:6px;margin-top:2px}
.dot{width:8px;height:8px;border-radius:50%;background:#4ADE80;flex:none}
.dot.off{background:#FB923C}
.search{margin-top:10px}
.search input{width:100%;min-height:44px;border-radius:10px;border:0;padding:0 14px;font-size:16px;background:#2C3035;color:#fff}
.search input::placeholder{color:#A9ADB2}
.chips{display:flex;gap:8px;overflow-x:auto;padding:12px 16px 4px;scrollbar-width:none}
.chips::-webkit-scrollbar{display:none}
.chip{flex:none;min-height:38px;padding:0 14px;border-radius:999px;border:1px solid #C9CAC4;background:#fff;color:var(--i2);font-size:13px;cursor:pointer}
.chip.on{background:var(--i);color:#fff;border-color:var(--i)}
main{padding:12px 16px calc(28px + env(safe-area-inset-bottom));max-width:720px;margin:0 auto}
.card{background:var(--s);border:1px solid var(--l);border-radius:14px;padding:16px;margin-bottom:12px}
.item{display:flex;gap:12px;align-items:center;text-decoration:none;color:inherit}
.item-body{flex:1;min-width:0}
.emp{font-weight:700;font-size:16px}
.proj{font-size:13px;color:#4F555C;margin-top:2px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.tag{font-size:11px;font-weight:700;padding:3px 8px;border-radius:5px;background:#E9E9E4;color:#3E434A}
.tag.inmediato{background:var(--a);color:#fff}.tag.mediano{background:var(--blb);color:var(--bl)}.tag.stand_by{background:#fff;border:1px dashed #8A8F96;color:#4F555C}
.tag.identificado,.tag.ok{background:var(--okb);color:var(--ok)}.tag.revision,.tag.asignado,.tag.warn{background:var(--amb);color:var(--am)}
.tag.alerta{background:#FBE3DA;color:#7C2D12}
.stack{display:flex;flex:none}
.stack .av{margin-left:-10px;border:2px solid #fff}
.stack .av:first-child{margin-left:0}
.av{width:44px;height:44px;border-radius:50%;object-fit:cover;flex:none;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;background:var(--st);font-size:15px}
.av.lg{width:64px;height:64px;font-size:20px}
.av.empty{background:#fff;border:1.5px dashed #8A8F96;color:var(--m)}
h2{font-size:18px;margin:0 0 8px}
h3{font-size:13px;letter-spacing:.06em;text-transform:uppercase;color:var(--m);margin:0 0 8px;font-weight:700}
.hero h2{font-size:22px;line-height:1.2}
.alerta{background:#FBE3DA;color:#7C2D12;border:1px solid #F0B9A6;border-radius:12px;padding:12px 14px;margin-bottom:12px;font-weight:600}
.warm{background:var(--w);border-color:var(--wl)}
.muted{color:var(--m)}.small{font-size:13px;color:#4F555C}
.btns{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px}
.btn{min-height:44px;padding:0 14px;border-radius:10px;border:1px solid #C9CAC4;background:#fff;color:var(--i);font-weight:600;font-size:14px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;cursor:pointer}
.btn.pri{background:var(--a);border-color:var(--a);color:#fff}
.btn.li{background:#0A66C2;border-color:#0A66C2;color:#fff}
.btn[aria-disabled=true]{opacity:.45;pointer-events:none}
.persona{display:flex;gap:12px;padding:14px 0;border-top:1px solid var(--l2)}
.persona:first-of-type{border-top:0}
.p-body{flex:1;min-width:0}
.p-name{font-weight:700}
.news{padding:12px 0;border-top:1px solid var(--l2)}
.news:first-child{border-top:0}
.news a{font-weight:600;color:var(--i);text-decoration:none}
.news .src{font-size:12px;color:var(--m);margin-top:4px}
.mats{display:flex;flex-wrap:wrap;gap:6px}
.mats span{font-size:13px;background:var(--g);border-radius:6px;padding:5px 9px}
.act{font-size:14px;padding:8px 0;border-top:1px solid var(--l2)}
.act:first-child{border-top:0}
.progress{height:6px;background:var(--l2);border-radius:3px;overflow:hidden;margin-top:10px}
.progress div{height:6px;background:var(--a);width:0}
.empty-state{text-align:center;color:var(--m);padding:40px 16px}
</style>
</head>
<body>
<header class="top">
    <div class="top-row">
        <button type="button" class="back" id="atq-back" aria-label="Volver" hidden><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 6-6 6 6 6"/></svg></button>
        <span class="logo" aria-hidden="true"></span>
        <h1 id="atq-titulo">Plan de Ataque</h1>
    </div>
    <div class="estado" aria-live="polite"><span class="dot" id="atq-dot"></span><span id="atq-estado">Cargando…</span></div>
    <div class="search" id="atq-search-wrap">
        <label for="atq-q" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Buscar empresa, proyecto o persona</label>
        <input id="atq-q" type="search" placeholder="Buscar empresa, proyecto o persona" autocomplete="off">
    </div>
</header>
<div class="chips" id="atq-chips" role="toolbar" aria-label="Filtrar por prioridad"></div>
<main id="atq-app" aria-live="polite"></main>
<script
    src="{{ asset('js/ataque-movil.js') }}"
    data-datos="{{ route('ataque.movil.datos') }}"
    data-noticias="{{ route('ataque.movil.noticias', ['proyecto' => '__ID__']) }}"
    data-noticias-persona="{{ route('ataque.movil.noticias.persona', ['decisor' => '__ID__']) }}"
    data-ficha="{{ route('ataque.show', ['proyecto' => '__ID__']) }}"
    data-sw="{{ route('ataque.movil.sw') }}"
    data-scope="{{ url(config('ataque.prefix')) }}/"
    defer></script>
</body>
</html>
