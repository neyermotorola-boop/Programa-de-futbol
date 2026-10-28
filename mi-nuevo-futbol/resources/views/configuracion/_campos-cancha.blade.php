{{-- Campos de una cancha: deporte, formato, superficie, precio, luces, techo y foto real.
     Recibe $cancha (null si es nueva), y $deportes / $superficies del negocio. --}}
@php
    $depSel = $cancha?->deporte_id ?? $deportes->first()?->id;
    $supSel = $cancha?->superficie_id ?? $superficies->first()?->id;
    $foto = $cancha?->urlFoto();
    $modo = $cancha?->foto_modo ?? 'entorno';
@endphp
<div class="grid-2">
    <div class="campo"><label>Deporte</label>
        <div class="control"><x-icon name="pitch" />
            <select name="deporte_id" data-3d="deporte">
                @foreach ($deportes as $d)
                    <option value="{{ $d->id }}" data-base="{{ $d->base }}" @selected($d->id == $depSel)>{{ $d->nombre }}</option>
                @endforeach
            </select></div></div>
    <div class="campo" data-campo-formato><label>Formato de juego</label>
        <div class="control"><x-icon name="users" />
            <select name="tamano" data-3d="formato" data-valor="{{ $cancha?->tamano }}"></select></div></div>
</div>
<div class="grid-2">
    <div class="campo"><label>Superficie</label>
        <div class="control"><x-icon name="layers" />
            <select name="superficie_id" data-3d="superficie">
                @foreach ($superficies as $s)
                    <option value="{{ $s->id }}" data-estilo="{{ $s->base }}" data-color="{{ $s->color }}" @selected($s->id == $supSel)>{{ $s->nombre }}</option>
                @endforeach
            </select></div></div>
    <div class="campo"><label>Precio por hora</label>
        <div class="control"><x-icon name="dollar" /><input type="number" name="precio_hora" value="{{ $cancha?->precio_hora ? (int) $cancha->precio_hora : '' }}" min="0" step="1000" placeholder="Ej: 80000"></div></div>
</div>
<div class="switches">
    <label class="switch"><input type="checkbox" name="iluminacion" value="1" data-3d="iluminacion" @checked($cancha ? $cancha->iluminacion : true)><span class="riel"></span> Iluminación nocturna</label>
    <label class="switch"><input type="checkbox" name="techada" value="1" data-3d="techada" @checked($cancha?->techada)><span class="riel"></span> Techada</label>
</div>

<div class="foto-cancha" data-foto="{{ $foto }}">
    <span class="label">Foto real de la cancha</span>
    <div class="foto-fila">
        <div class="foto-prev" data-foto-prev @if ($foto) style="background-image:url('{{ $foto }}')" @endif>@unless ($foto)<x-icon name="eye" size="22" />@endunless</div>
        <div class="foto-ctrl">
            <div class="foto-botones">
                <label class="btn btn-secondary btn-sm" style="margin:0"><x-icon name="plus" size="15" /> <span data-foto-texto>{{ $foto ? 'Cambiar foto' : 'Subir foto' }}</span>
                    <input type="file" name="foto" accept="image/png,image/jpeg,image/webp" data-foto-input hidden></label>
                @if ($foto)
                    <label class="switch" style="font-size:12.5px"><input type="checkbox" name="quitar_foto" value="1" data-quitar-foto><span class="riel"></span> Quitar</label>
                @endif
            </div>
            <div class="control" style="margin-top:8px"><x-icon name="eye" />
                <select name="foto_modo" data-3d="fotoModo" aria-label="Cómo usar la foto en el 3D">
                    <option value="entorno" @selected($modo === 'entorno')>En el 3D: rodea la cancha</option>
                    <option value="piso" @selected($modo === 'piso')>En el 3D: cubre la cancha (vista aérea)</option>
                </select></div>
            <div class="ayuda">Tus clientes la ven dentro del modelo 3D, y pueden cambiar a la foto real. JPG, PNG o WEBP · 6 MB.</div>
        </div>
    </div>
</div>
