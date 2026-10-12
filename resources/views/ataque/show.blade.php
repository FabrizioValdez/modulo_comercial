@extends(config('ataque.layout'))

@section('title', $proyecto->empresa)

@section('content')
@include('ataque.partials.estilos')
@php
    $soles = function ($n) { return 'S/ ' . number_format((float) $n, 0, '.', ','); };
    $anio = date('Y');
@endphp
<div class="atq">
<main class="atq-main">
    <div class="atq-breadcrumb"><a href="{{ route('ataque.index') }}">Plan de ataque</a> / <span>{{ $proyecto->empresa }}</span></div>

    @if (session('ok'))<div class="atq-flash">{{ session('ok') }}</div>@endif
    @if ($errors->any())<div class="atq-flash atq-flash--error">{{ $errors->first() }}</div>@endif

    <section class="atq-card atq-hero">
        <div class="atq-hero-main">
            <div class="atq-eyebrow atq-eyebrow--muted">{{ $proyecto->empresa }} · {{ $proyecto->rubro }}</div>
            <h1 class="atq-h1">{{ $proyecto->proyecto }}</h1>
            <div class="atq-chips">
                <span class="atq-chip atq-chip--{{ $proyecto->prioridad }}">{{ $proyecto->prioridad_label }}</span>
                @if ($proyecto->viabilidad)<span class="atq-chip atq-chip--ok">Viabilidad {{ $proyecto->viabilidad }}</span>@endif
                @if ($proyecto->inversion)<span class="atq-chip atq-chip--data">{{ $proyecto->inversion }}</span>@endif
                @if ($proyecto->ejecucion)<span class="atq-chip atq-chip--data">{{ $proyecto->ejecucion }}</span>@endif
                @if ($proyecto->subcontratista)<span class="atq-chip atq-chip--blue">Contratista: {{ $proyecto->subcontratista }}</span>@endif
            </div>
        </div>
        <div class="atq-hero-actions">
            <a href="#actividad" class="atq-btn">Registrar actividad</a>
            <a href="{{ route('ataque.investigar', $proyecto) }}" class="atq-btn atq-btn--primary">Prompt de investigación</a>
        </div>
    </section>

    @if ($proyecto->alerta)
        <div class="atq-alerta" role="alert"><strong>Atención:</strong> {{ $proyecto->alerta }}</div>
    @endif

    <div class="atq-cols">
        <div class="atq-col-main">

            @if ($proyecto->datos_empresa)
            <section class="atq-card">
                <h2 class="atq-h2">La empresa hoy</h2>
                <p class="atq-p">{{ $proyecto->datos_empresa }}</p>
                @if ($proyecto->datos_fuente)<div class="atq-mono-sm">Fuente: {{ $proyecto->datos_fuente }}</div>@endif
            </section>
            @endif

            {{-- LO ACTUAL --}}
            <section class="atq-card atq-card--warm">
                <div class="atq-row-between">
                    <h2 class="atq-h2 atq-accent">Lo actual</h2>
                    @if ($proyecto->resumen_ia_at)
                        <span class="atq-mono-sm">Actualizado {{ $proyecto->resumen_ia_at->format('d/m/Y') }}</span>
                    @endif
                </div>
                @if ($proyecto->resumen_ia)
                    <p class="atq-p">{{ $proyecto->resumen_ia }}</p>
                @else
                    <p class="atq-p atq-muted">Aún no hay resumen. Genera el prompt, úsalo en tu herramienta de IA y pega la respuesta: el resumen aparece aquí.</p>
                @endif
                @if ($proyecto->ultimaInvestigacion)
                    <a href="{{ route('ataque.investigacion.show', $proyecto->ultimaInvestigacion) }}" class="atq-link-strong">Ver último informe</a>
                @endif
            </section>

            {{-- CLIENTE EN TU BASE: AÑO ACTUAL --}}
            <section class="atq-card">
                <div class="atq-row-between">
                    <h2 class="atq-h2">Cliente en tu base · {{ $anio }}</h2>
                    @if ($cliente)
                        @if ($facturacion && !$facturacion['error'] && $facturacion['facturas'] > 0)
                            <span class="atq-badge atq-badge--ok">Cliente activo</span>
                        @else
                            <span class="atq-badge atq-badge--blue">Registrado sin compras en {{ $anio }}</span>
                        @endif
                    @else
                        <span class="atq-badge atq-badge--dashed">No relacionado</span>
                    @endif
                </div>

                @if ($cliente)
                    <div class="atq-client">
                        <div><div class="atq-strong">{{ $cliente->name_social }}</div><div class="atq-mono-sm">RUC {{ $cliente->number_document }}</div></div>
                        <form method="post" action="{{ route('ataque.relacionar', $proyecto) }}">
                            @csrf
                            <input type="hidden" name="tipo" value="cliente">
                            <button type="submit" class="atq-btn atq-btn--sm">Quitar relación</button>
                        </form>
                    </div>
                    @if ($facturacion && $facturacion['error'])
                        <p class="atq-small atq-warn">No se pudo leer el ERP en este momento.</p>
                    @elseif ($facturacion)
                        <div class="atq-stats">
                            <div class="atq-stat"><div class="atq-small">Facturado {{ $anio }} (neto sin IGV)</div><div class="atq-stat-v">{{ $soles($facturacion['neto']) }}</div></div>
                            <div class="atq-stat"><div class="atq-small">Facturas {{ $anio }}</div><div class="atq-stat-v">{{ $facturacion['facturas'] }}</div></div>
                            <div class="atq-stat"><div class="atq-small">Última factura</div><div class="atq-stat-v">{{ $facturacion['ultima'] ? \Carbon\Carbon::parse($facturacion['ultima'])->format('d/m/Y') : '—' }}</div></div>
                            <div class="atq-stat"><div class="atq-small">Saldo pendiente</div><div class="atq-stat-v">{{ $soles($facturacion['pendiente']) }}</div></div>
                            @if ($cotizaciones)
                                <div class="atq-stat"><div class="atq-small">Cotizaciones {{ $anio }}</div><div class="atq-stat-v">{{ $cotizaciones['cantidad'] }}</div></div>
                            @endif
                        </div>
                    @endif
                @else
                    @if ($candidatos->isNotEmpty())
                        <p class="atq-small">Clientes con nombre parecido. Relaciona solo si el RUC es el correcto.</p>
                        <ul class="atq-list">
                            @foreach ($candidatos as $c)
                                <li class="atq-list-row">
                                    <div><div class="atq-strong">{{ $c->name_social }}</div><div class="atq-mono-sm">RUC {{ $c->number_document }}</div></div>
                                    <form method="post" action="{{ route('ataque.relacionar', $proyecto) }}">
                                        @csrf
                                        <input type="hidden" name="tipo" value="cliente">
                                        <input type="hidden" name="client_id" value="{{ $c->id }}">
                                        <button type="submit" class="atq-btn atq-btn--sm">Relacionar</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="atq-small">No hay clientes con un nombre parecido en el CRM.</p>
                    @endif
                    <form method="post" action="{{ route('ataque.relacionar', $proyecto) }}" class="atq-inline-form">
                        @csrf
                        <input type="hidden" name="tipo" value="cliente">
                        <label for="ruc-cliente" class="atq-small">Relacionar por RUC</label>
                        <input id="ruc-cliente" name="ruc" inputmode="numeric" maxlength="11" placeholder="20XXXXXXXXX">
                        <button type="submit" class="atq-btn atq-btn--sm">Relacionar</button>
                    </form>
                @endif

                @if ($proyecto->subcontratista || $contratista)
                    <div class="atq-contractor">
                        <div class="atq-strong">Contratista: {{ $contratista ? $contratista->name_social : $proyecto->subcontratista }}</div>
                        @if ($contratista)
                            <div class="atq-small">
                                RUC {{ $contratista->number_document }} ·
                                @if ($contratistaFact && !$contratistaFact['error'])
                                    {{ $contratistaFact['facturas'] > 0 ? $soles($contratistaFact['neto']) . ' facturados en ' . $anio : 'sin facturas en ' . $anio }}
                                @endif
                            </div>
                        @else
                            <form method="post" action="{{ route('ataque.relacionar', $proyecto) }}" class="atq-inline-form">
                                @csrf
                                <input type="hidden" name="tipo" value="contratista">
                                <label for="ruc-contratista" class="atq-small">RUC del contratista</label>
                                <input id="ruc-contratista" name="ruc" inputmode="numeric" maxlength="11" placeholder="20XXXXXXXXX">
                                <button type="submit" class="atq-btn atq-btn--sm">Relacionar</button>
                            </form>
                        @endif
                    </div>
                @endif
            </section>

            {{-- QUÉ VENDERLE --}}
            @if ($proyecto->materiales || $proyecto->proximo_paso)
            <section class="atq-card">
                <h2 class="atq-h2">Qué venderle</h2>
                <div class="atq-chips">
                    @foreach ($proyecto->materiales_lista as $m)
                        <span class="atq-chip atq-chip--data">{{ $m }}</span>
                    @endforeach
                </div>
                @if ($proyecto->proximo_paso)
                    <div class="atq-next"><span class="atq-mono-sm">Próximo paso</span><p class="atq-p">{{ $proyecto->proximo_paso }}</p></div>
                @endif
            </section>
            @endif

            {{-- ACTIVIDAD --}}
            <section class="atq-card" id="actividad">
                <h2 class="atq-h2">Actividad reciente</h2>
                <ol class="atq-timeline">
                    @forelse ($actividades as $a)
                        <li>
                            <span class="atq-dot"></span>
                            <div class="atq-tl-body">{{ $a->descripcion }}</div>
                            <span class="atq-mono-sm">{{ $a->fecha ? $a->fecha->format('d/m/Y') : $a->created_at->format('d/m/Y') }}</span>
                        </li>
                    @empty
                        <li class="atq-muted">Sin actividad registrada.</li>
                    @endforelse
                </ol>
                <form method="post" action="{{ route('ataque.actividad', $proyecto) }}" class="atq-form">
                    @csrf
                    <label for="act-desc">Nueva actividad</label>
                    <textarea id="act-desc" name="descripcion" rows="2" required placeholder="Ej. Llamada con el gerente de obra: piden cotización de corrugado para noviembre"></textarea>
                    <div class="atq-form-row">
                        <div><label for="act-fecha">Fecha</label><input id="act-fecha" type="date" name="fecha" value="{{ date('Y-m-d') }}"></div>
                        <div class="atq-grow"><label for="act-next">Nuevo próximo paso (opcional)</label><input id="act-next" name="proximo_paso"></div>
                    </div>
                    <button type="submit" class="atq-btn atq-btn--primary">Guardar actividad</button>
                </form>
            </section>
        </div>

        {{-- DECISORES --}}
        <aside class="atq-col-side">
            <section class="atq-card">
                <h2 class="atq-h2">Decisores clave</h2>
                <p class="atq-small">Foto subida por el equipo. Sin foto se muestran las iniciales.</p>
                @foreach ($proyecto->decisores as $d)
                    <div class="atq-decisor">
                        <div class="atq-person">
                            @include('ataque.partials.avatar', ['d' => $d, 'size' => 60])
                            <div class="atq-grow">
                                <div class="{{ $d->nombre ? 'atq-strong' : 'atq-muted' }}">{{ $d->nombre ?: 'Por identificar' }}</div>
                                <div class="atq-small">{{ $d->cargo }}</div>
                                @if ($d->universidad)<div class="atq-mono-sm">{{ $d->universidad }}</div>@endif
                                @if ($d->notas)<div class="atq-small atq-warn">{{ $d->notas }}</div>@endif
                                <div class="atq-chips atq-chips--tight">
                                    <span class="atq-chip atq-chip--role-{{ $d->rol_compra }}">{{ $d->rol_label }}</span>
                                    @if ($d->sugerido_ia)<span class="atq-chip atq-chip--warn">Sugerido por IA</span>@endif
                                    @if ($d->estado_contacto)<span class="atq-chip atq-chip--ok">{{ $d->estado_contacto }}</span>@endif
                                </div>
                            </div>
                        </div>
                        <div class="atq-decisor-links">
                            @if ($d->linkedin_enlace)
                                <a href="{{ $d->linkedin_enlace }}" target="_blank" rel="noopener">{{ $d->linkedin_url ? 'Perfil LinkedIn' : 'Buscar en LinkedIn' }}</a>
                            @endif
                        </div>
                        <details class="atq-details">
                            <summary>Editar o cambiar foto</summary>
                            @include('ataque.partials.form-decisor', ['d' => $d, 'action' => route('ataque.decisores.update', $d), 'method' => 'PUT'])
                            <form method="post" action="{{ route('ataque.decisores.destroy', $d) }}" onsubmit="return confirm('¿Eliminar a este decisor?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="atq-btn atq-btn--sm atq-btn--danger">Eliminar</button>
                            </form>
                        </details>
                    </div>
                @endforeach
                <details class="atq-details">
                    <summary>Agregar decisor</summary>
                    @include('ataque.partials.form-decisor', ['d' => null, 'action' => route('ataque.decisores.store', $proyecto), 'method' => 'POST'])
                </details>
            </section>
        </aside>
    </div>
</main>
</div>
@endsection
