@extends(config('ataque.layout'))

@section('title', 'Plan de ataque comercial')

@section('content')
@include('ataque.partials.estilos')
<div class="atq">
<main class="atq-main">
    <div class="atq-head">
        <div>
            <div class="atq-eyebrow">Plan estratégico · Ataque comercial</div>
            <h1 class="atq-h1">Plan de ataque comercial</h1>
            <p class="atq-muted">{{ $conteos->sum() }} cuentas. Cada una con su proyecto, decisores y próximo paso.</p>
        </div>
    </div>

    @if (session('ok'))<div class="atq-flash">{{ session('ok') }}</div>@endif

    <div class="atq-kpis">
        <div class="atq-card"><div class="atq-kpi-label">Cuentas en el plan</div><div class="atq-kpi">{{ $conteos->sum() }}</div></div>
        <div class="atq-card atq-card--dark"><div class="atq-kpi-label">Ataque inmediato</div><div class="atq-kpi atq-kpi--accent">{{ $conteos->get('inmediato', 0) }}</div></div>
        <div class="atq-card"><div class="atq-kpi-label">Sin decisor con nombre</div><div class="atq-kpi">{{ $sinDecisor }}</div></div>
    </div>

    <section class="atq-card atq-card--flush">
        <div class="atq-toolbar">
            <nav class="atq-pills" aria-label="Filtrar por prioridad">
                <a href="{{ route('ataque.index', array_filter(['q' => $q])) }}" class="atq-pill {{ $prioridad ? '' : 'is-on' }}">Todas <span>{{ $conteos->sum() }}</span></a>
                @foreach (config('ataque.prioridades') as $clave => $label)
                    @if ($conteos->get($clave))
                        <a href="{{ route('ataque.index', array_filter(['prioridad' => $clave, 'q' => $q])) }}" class="atq-pill {{ $prioridad === $clave ? 'is-on' : '' }}">{{ $label }} <span>{{ $conteos->get($clave) }}</span></a>
                    @endif
                @endforeach
            </nav>
            <form method="get" action="{{ route('ataque.index') }}" class="atq-search">
                @if ($prioridad)<input type="hidden" name="prioridad" value="{{ $prioridad }}">@endif
                <label for="atq-q" class="atq-sr">Buscar cuenta, proyecto o persona</label>
                <input id="atq-q" type="search" name="q" value="{{ $q }}" placeholder="Buscar cuenta, proyecto o persona">
            </form>
        </div>

        <div class="atq-scroll">
            <table class="atq-table">
                <thead>
                    <tr><th>Cuenta / proyecto</th><th>Decisor clave</th><th>Inversión</th><th>Ejecución</th><th>Prioridad</th><th>Próximo paso</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($proyectos as $p)
                    @php $d = $p->decisores->first(); @endphp
                    <tr>
                        <td>
                            <div class="atq-strong">{{ $p->empresa }}</div>
                            <div class="atq-small">{{ \Illuminate\Support\Str::limit($p->proyecto, 90) }}</div>
                            <div class="atq-mono-sm">{{ $p->rubro }}</div>
                        </td>
                        <td>
                            <div class="atq-person">
                                @include('ataque.partials.avatar', ['d' => $d, 'size' => 40])
                                <div>
                                    <div class="{{ $d && $d->nombre ? 'atq-strong' : 'atq-muted' }}">{{ $d && $d->nombre ? $d->nombre : 'Por identificar' }}</div>
                                    <div class="atq-small">{{ $d ? \Illuminate\Support\Str::limit($d->cargo, 40) : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="atq-mono">{{ $p->inversion ?: '—' }}</td>
                        <td class="atq-mono">{{ $p->ejecucion ?: '—' }}</td>
                        <td><span class="atq-chip atq-chip--{{ $p->prioridad }}">{{ $p->prioridad_label }}</span></td>
                        <td class="atq-small">{{ \Illuminate\Support\Str::limit($p->proximo_paso, 110) }}</td>
                        <td class="atq-actions">
                            <a href="{{ route('ataque.show', $p) }}" class="atq-link-strong">Abrir ficha</a>
                            <a href="{{ route('ataque.investigar', $p) }}">Prompt IA</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="atq-empty">Ninguna cuenta coincide con la búsqueda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
</div>
@endsection
