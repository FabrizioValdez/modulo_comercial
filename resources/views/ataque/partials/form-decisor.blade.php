@php $pre = $d ? 'd' . $d->id . '-' : 'nuevo-'; @endphp
<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="atq-form">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif
    <label for="{{ $pre }}nombre">Nombre</label>
    <input id="{{ $pre }}nombre" name="nombre" value="{{ $d ? $d->nombre : '' }}">
    <label for="{{ $pre }}cargo">Cargo</label>
    <input id="{{ $pre }}cargo" name="cargo" value="{{ $d ? $d->cargo : '' }}">
    <label for="{{ $pre }}rol">Rol en la compra</label>
    <select id="{{ $pre }}rol" name="rol_compra">
        @foreach (config('ataque.roles_compra') as $k => $v)
            <option value="{{ $k }}" @if ($d && $d->rol_compra === $k) selected @endif>{{ $v }}</option>
        @endforeach
    </select>
    <label for="{{ $pre }}estado">Estado del contacto</label>
    <input id="{{ $pre }}estado" name="estado_contacto" value="{{ $d ? $d->estado_contacto : '' }}" placeholder="Ej. Contactado">
    <label for="{{ $pre }}li">URL de LinkedIn</label>
    <input id="{{ $pre }}li" name="linkedin_url" type="url" value="{{ $d ? $d->linkedin_url : '' }}" placeholder="https://www.linkedin.com/in/...">
    <label for="{{ $pre }}foto">Foto</label>
    <input id="{{ $pre }}foto" name="foto" type="file" accept="image/*">
    <button type="submit" class="atq-btn atq-btn--primary atq-btn--sm">Guardar</button>
</form>
