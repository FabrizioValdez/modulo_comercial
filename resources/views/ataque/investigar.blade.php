@extends(config('ataque.layout'))

@section('title', 'Prompt de investigación · ' . $proyecto->empresa)

@section('content')
@include('ataque.partials.estilos')
<div class="atq">
<main class="atq-main">
    <div class="atq-breadcrumb"><a href="{{ route('ataque.index') }}">Plan de ataque</a> / <a href="{{ route('ataque.show', $proyecto) }}">{{ $proyecto->empresa }}</a> / <span>Investigación</span></div>
    <div>
        <h1 class="atq-h1">Investigar proyecto y personas</h1>
        <p class="atq-muted">1. Elige qué investigar. 2. Copia el prompt y úsalo en tu herramienta de IA con búsqueda web. 3. Pega la respuesta abajo y se guarda en la ficha.</p>
    </div>

    @if ($errors->any())<div class="atq-flash atq-flash--error">{{ $errors->first() }}</div>@endif

    <div class="atq-cols">
        <aside class="atq-col-side">
            <form method="get" action="{{ route('ataque.investigar', $proyecto) }}" class="atq-card atq-form">
                <h2 class="atq-h2">1 · Qué investigar</h2>

                <fieldset class="atq-fieldset">
                    <legend>Personas</legend>
                    @foreach ($proyecto->decisores as $d)
                        <label class="atq-check">
                            <input type="checkbox" name="decisores[]" value="{{ $d->id }}" @if (empty($decisorIds) || in_array($d->id, $decisorIds, true)) checked @endif>
                            <span><b>{{ $d->nombre ?: 'Por identificar' }}</b><br><small>{{ $d->cargo }}</small></span>
                        </label>
                    @endforeach
                    @if ($proyecto->decisores->isEmpty())<p class="atq-small">Sin personas: el prompt pedirá identificar a los decisores.</p>@endif
                </fieldset>

                <fieldset class="atq-fieldset">
                    <legend>Dónde buscar</legend>
                    @foreach (config('ataque.fuentes') as $k => $v)
                        <label class="atq-check">
                            <input type="checkbox" name="fuentes[]" value="{{ $k }}" @if (in_array($k, $fuentes, true)) checked @endif>
                            <span>{{ $v }}</span>
                        </label>
                    @endforeach
                </fieldset>

                <fieldset class="atq-fieldset">
                    <legend>Novedades de los últimos</legend>
                    <div class="atq-seg">
                        @foreach ([30, 90, 180] as $n)
                            <label><input type="radio" name="dias" value="{{ $n }}" @if ($dias === $n) checked @endif> {{ $n }} días</label>
                        @endforeach
                    </div>
                </fieldset>

                <button type="submit" class="atq-btn">Actualizar prompt</button>
            </form>
        </aside>

        <div class="atq-col-main">
            <section class="atq-card">
                <div class="atq-row-between">
                    <h2 class="atq-h2">2 · Prompt</h2>
                    <button type="button" class="atq-btn atq-btn--primary" id="atq-copiar">Copiar prompt</button>
                </div>
                <label for="atq-prompt" class="atq-sr">Prompt</label>
                <textarea id="atq-prompt" class="atq-code" rows="22" readonly>{{ $prompt }}</textarea>
                <p class="atq-small">Úsalo en una herramienta con búsqueda web activada para que traiga avances y perfiles públicos con sus fuentes.</p>
            </section>

            <section class="atq-card">
                <h2 class="atq-h2">3 · Pega la respuesta</h2>
                <form method="post" action="{{ route('ataque.investigacion.store', $proyecto) }}" class="atq-form">
                    @csrf
                    <input type="hidden" name="prompt" value="{{ $prompt }}">
                    <input type="hidden" name="parametros" value="{{ json_encode(['decisores' => $decisorIds, 'fuentes' => $fuentes, 'dias' => $dias]) }}">
                    <label for="inv-herr">Herramienta usada (opcional)</label>
                    <input id="inv-herr" name="herramienta" placeholder="Ej. Claude, ChatGPT, Perplexity">
                    <label for="inv-resp">Respuesta completa</label>
                    <textarea id="inv-resp" name="respuesta" rows="12" required placeholder="Pega aquí la respuesta. Si viene en JSON, la ficha se llena sola."></textarea>
                    <label for="inv-res">Resumen de lo actual para la ficha (opcional)</label>
                    <textarea id="inv-res" name="resumen_actual" rows="3" placeholder="Si lo dejas vacío se usa el campo resumen_actual del JSON."></textarea>
                    <button type="submit" class="atq-btn atq-btn--primary">Guardar en la ficha</button>
                </form>
            </section>

            @if ($historial->isNotEmpty())
            <section class="atq-card">
                <h2 class="atq-h2">Informes anteriores</h2>
                <ul class="atq-list">
                    @foreach ($historial as $h)
                        <li class="atq-list-row">
                            <span>{{ $h->created_at->format('d/m/Y H:i') }} · {{ $h->herramienta ?: 'sin herramienta' }} · {{ $h->resultado ? 'JSON' : 'texto' }}</span>
                            <a href="{{ route('ataque.investigacion.show', $h) }}">Ver</a>
                        </li>
                    @endforeach
                </ul>
            </section>
            @endif
        </div>
    </div>
</main>
</div>
<script>
(function () {
    var btn = document.getElementById('atq-copiar');
    var txt = document.getElementById('atq-prompt');
    if (!btn || !txt) { return; }
    btn.addEventListener('click', function () {
        var listo = function () { btn.textContent = 'Copiado'; setTimeout(function () { btn.textContent = 'Copiar prompt'; }, 2000); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(txt.value).then(listo);
        } else {
            txt.select(); document.execCommand('copy'); listo();
        }
    });
})();
</script>
@endsection
