{{-- $d: AtaqueDecisor|null · $size: px --}}
@php $size = $size ?? 40; @endphp
@if ($d && $d->foto_url)
    <img src="{{ $d->foto_url }}" alt="Foto de {{ $d->nombre }}" class="atq-avatar" style="width: {{ $size }}px; height: {{ $size }}px">
@elseif ($d && $d->nombre)
    <span class="atq-avatar atq-avatar--ini" style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.34) }}px; background: {{ ['#2B4C66', '#4D5B3A', '#7A3A14', '#3E434A', '#5A3E66'][$d->id % 5] }}" aria-hidden="true">{{ $d->getIniciales() }}</span>
@else
    <span class="atq-avatar atq-avatar--vacio" style="width: {{ $size }}px; height: {{ $size }}px" aria-hidden="true">
        <svg width="{{ round($size * 0.45) }}" height="{{ round($size * 0.45) }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
    </span>
@endif
