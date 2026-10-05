@extends(config('ataque.layout'))

@section('title', 'Informe · ' . $proyecto->empresa)

@section('content')
@include('ataque.partials.estilos')
@php
    // Solo enlaces http(s): la respuesta la pega el usuario y no se confía en ella.
    $url = function ($u) { $u = trim((string) $u); return preg_match('#^https?://#i', $u) ? $u : null; };
    $v = function ($a, $k) { return isset($a[$k]) && $a[$k] !== '' ? $a[$k] : 'sin dato'; };
@endphp
<div class="atq">
<main class="atq-main">
    <div class="atq-breadcrumb"><a href="{{ route('ataque.index') }}">Plan de ataque</a> / <a href="{{ route('ataque.show', $proyecto) }}">{{ $proyecto->empresa }}</a> / <span>Informe {{ $inv->created_at->format('d/m/Y') }}</span></div>
    @if (session('ok'))<div class="atq-flash">{{ session('ok') }}</div>@endif

    <h1 class="atq-h1">Informe de investigación</h1>
    <p class="atq-muted">{{ $proyecto->empresa }} · {{ $inv->created_at->format('d/m/Y H:i') }} · {{ $inv->herramienta ?: 'herramienta no indicada' }}</p>

    @if (empty($r))
        <section class="atq-card">
            <h2 class="atq-h2">Respuesta (texto)</h2>
            <pre class="atq-pre">{{ $inv->respuesta }}</pre>
        </section>
    @else
        @if (!empty($r['resumen_actual']))
            <section class="atq-card atq-card--warm"><h2 class="atq-h2 atq-accent">Lo actual</h2><p class="atq-p">{{ $r['resumen_actual'] }}</p></section>
        @endif

        @if (!empty($r['estado_proyecto']) && is_array($r['estado_proyecto']))
            @php $e = $r['estado_proyecto']; @endphp
            <section class="atq-stats">
                <div class="atq-stat"><div class="atq-small">Etapa</div><div class="atq-stat-v">{{ $v($e, 'etapa') }}</div></div>
                <div class="atq-stat"><div class="atq-small">Avance</div><div class="atq-stat-v">{{ $v($e, 'avance') }}</div></div>
                <div class="atq-stat"><div class="atq-small">Último hito</div><div class="atq-stat-v">{{ $v($e, 'ultimo_hito') }}</div></div>
                <div class="atq-stat"><div class="atq-small">Fecha</div><div class="atq-stat-v">{{ $v($e, 'fecha') }}</div></div>
            </section>
        @endif

        @if (!empty($r['novedades']) && is_array($r['novedades']))
            <section class="atq-card">
                <h2 class="atq-h2">Novedades</h2>
                <ul class="atq-list">
                    @foreach ($r['novedades'] as $n)
                        <li class="atq-list-row atq-list-row--top">
                            <span class="atq-mono-sm">{{ $v($n, 'fecha') }}</span>
                            <div class="atq-grow"><div class="atq-strong">{{ $v($n, 'titulo') }}</div><div class="atq-small">{{ $n['resumen'] ?? '' }}</div></div>
                            @if ($url($n['url'] ?? ''))<a href="{{ $url($n['url']) }}" target="_blank" rel="noopener noreferrer">Fuente</a>@endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($r['mapa_decision']) && is_array($r['mapa_decision']))
            <section class="atq-card">
                <h2 class="atq-h2">Quién decide la compra</h2>
                <ul class="atq-list">
                    @foreach ($r['mapa_decision'] as $m)
                        <li class="atq-list-row"><span class="atq-chip atq-chip--data">{{ $v($m, 'rol') }}</span><div class="atq-grow"><b>{{ $v($m, 'nombre') }}</b> · {{ $m['cargo'] ?? '' }} · {{ $m['empresa'] ?? '' }}</div></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (!empty($r['personas']) && is_array($r['personas']))
            <section class="atq-card">
                <h2 class="atq-h2">Personas</h2>
                @foreach ($r['personas'] as $p)
                    <div class="atq-persona">
                        <div class="atq-row-between">
                            <div><div class="atq-strong">{{ $v($p, 'nombre') }}</div><div class="atq-small">{{ $p['cargo'] ?? '' }} @if (!empty($p['desde'])) · desde {{ $p['desde'] }} @endif</div></div>
                            <span class="atq-chip atq-chip--data">Confianza {{ $v($p, 'confianza') }}</span>
                        </div>
                        <dl class="atq-dl">
                            <dt>Trayectoria</dt><dd>{{ $v($p, 'trayectoria') }}</dd>
                            <dt>Publicaciones recientes</dt><dd>{{ $v($p, 'publicaciones') }}</dd>
                            <dt>Temas que le importan</dt><dd>{{ $v($p, 'temas') }}</dd>
                            <dt>Puntos de conexión</dt><dd>{{ $v($p, 'conexiones') }}</dd>
                            <dt class="atq-accent">Cómo abordarlo</dt><dd>{{ $v($p, 'como_abordar') }}</dd>
                        </dl>
                        @if ($url($p['linkedin'] ?? ''))<a href="{{ $url($p['linkedin']) }}" target="_blank" rel="noopener noreferrer">LinkedIn</a>@endif
                    </div>
                @endforeach
            </section>
        @endif

        @if (!empty($r['angulo_comercial']) && is_array($r['angulo_comercial']))
            @php $a = $r['angulo_comercial']; @endphp
            <section class="atq-card atq-card--warm">
                <h2 class="atq-h2 atq-accent">Ángulo comercial</h2>
                <dl class="atq-dl">
                    <dt>Qué ofrecer</dt><dd>{{ $v($a, 'que') }}</dd>
                    <dt>A quién</dt><dd>{{ $v($a, 'a_quien') }}</dd>
                    <dt>Cuándo</dt><dd>{{ $v($a, 'cuando') }}</dd>
                    <dt>Riesgos</dt><dd>{{ $v($a, 'riesgos') }}</dd>
                </dl>
            </section>
        @endif

        @if (!empty($r['fuentes']) && is_array($r['fuentes']))
            <section class="atq-card">
                <h2 class="atq-h2">Fuentes</h2>
                <ol class="atq-sources">
                    @foreach ($r['fuentes'] as $f)
                        <li>
                            {{ $v($f, 'titulo') }} · {{ $f['medio'] ?? '' }} · {{ $f['fecha'] ?? '' }}
                            @if ($url($f['url'] ?? '')) · <a href="{{ $url($f['url']) }}" target="_blank" rel="noopener noreferrer">abrir</a>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endif
    @endif

    <details class="atq-details"><summary>Ver prompt usado</summary><pre class="atq-pre">{{ $inv->prompt }}</pre></details>
    <a href="{{ route('ataque.show', $proyecto) }}" class="atq-btn">Volver a la ficha</a>
</main>
</div>
@endsection
